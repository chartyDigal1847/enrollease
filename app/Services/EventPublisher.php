<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EventOutbox;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * EventPublisher — publishes enrollment events to the DEORIS Event Hub.
 *
 * Uses the transactional outbox pattern:
 *   1. Write event to event_outbox (same DB transaction as the business action)
 *   2. Background job reads pending events and POSTs to DEORIS
 *
 * HMAC-SHA256 signing matches DEORIS EventIngestController expectations.
 */
class EventPublisher
{
    private string $secret;
    private string $portalUrl;
    private bool   $enabled;

    public function __construct()
    {
        $this->secret    = (string) config('enrollease.event_secret', env('ENROLLEASE_EVENT_SECRET', ''));
        $this->portalUrl = rtrim((string) config('app.portal_url', 'https://deoris.test'), '/');
        $this->enabled   = (bool) config('enrollease.publish_enabled', env('DEORIS_PORTAL_PUBLISH_ENABLED', false));
    }

    // ── Domain events ────────────────────────────────────────────────────────

    public function studentEnrolled(Enrollment $enrollment): void
    {
        $enrollment->loadMissing(['room', 'student']);

        $this->queue('StudentEnrolled', [
            'enrollment_id'  => $enrollment->id,
            'student_name'   => $enrollment->student_name,
            'student_email'  => $enrollment->email,
            'email'          => $enrollment->email,
            'user_id'        => $this->deorisUserId($enrollment),
            'local_student_id' => $enrollment->student_id,
            'grade_level'    => $enrollment->grade_level,
            'school_year'    => $enrollment->school_year,
            'room'           => $enrollment->room?->name,
            'section'        => $enrollment->room?->section,
            'program'        => 'Grade ' . $enrollment->grade_level,
            'lrn'            => $enrollment->lrn,
            'first_name'     => $enrollment->first_name,
            'last_name'      => $enrollment->last_name,
            'gender'         => $enrollment->gender,
        ]);
    }

    public function enrollmentApproved(Enrollment $enrollment): void
    {
        $enrollment->loadMissing('student');

        $this->queue('EnrollmentApproved', [
            'enrollment_id' => $enrollment->id,
            'student_name'  => $enrollment->student_name,
            'student_email' => $enrollment->email,
            'user_id'       => $this->deorisUserId($enrollment),
            'local_student_id' => $enrollment->student_id,
            'grade_level'   => $enrollment->grade_level,
            'school_year'   => $enrollment->school_year,
        ]);
    }

    public function enrollmentRejected(Enrollment $enrollment): void
    {
        $enrollment->loadMissing('student');

        $this->queue('EnrollmentRejected', [
            'enrollment_id' => $enrollment->id,
            'student_name'  => $enrollment->student_name,
            'student_email' => $enrollment->email,
            'user_id'       => $this->deorisUserId($enrollment),
            'local_student_id' => $enrollment->student_id,
            'remarks'       => $enrollment->remarks,
        ]);
    }

    public function sectionAssigned(Enrollment $enrollment): void
    {
        $enrollment->loadMissing(['room', 'student']);

        $this->queue('SectionAssigned', [
            'enrollment_id' => $enrollment->id,
            'student_name'  => $enrollment->student_name,
            'student_email' => $enrollment->email,
            'user_id'       => $this->deorisUserId($enrollment),
            'local_student_id' => $enrollment->student_id,
            'room_id'       => $enrollment->room_id,
            'room_name'     => $enrollment->room?->name,
            'section'       => $enrollment->room?->section,
            'grade_level'   => $enrollment->grade_level,
        ]);
    }

    public function enrollmentCancelled(Enrollment $enrollment): void
    {
        $enrollment->loadMissing('student');

        $this->queue('EnrollmentCancelled', [
            'enrollment_id' => $enrollment->id,
            'student_name'  => $enrollment->student_name,
            'student_email' => $enrollment->email,
            'user_id'       => $this->deorisUserId($enrollment),
            'local_student_id' => $enrollment->student_id,
        ]);
    }

    private function deorisUserId(Enrollment $enrollment): ?int
    {
        return $enrollment->student?->deoris_user_id;
    }

    // ── Outbox write ─────────────────────────────────────────────────────────

    private function queue(string $eventName, array $payload): void
    {
        if (! $this->enabled) {
            Log::debug("[EnrollEase][Events] Publishing disabled — skipping {$eventName}");
            return;
        }

        EventOutbox::queue($eventName, $payload);
    }

    // ── Publish pending outbox events (called by queue job) ──────────────────

    public function publishPending(): int
    {
        $published = 0;

        EventOutbox::where('status', 'pending')
            ->where('attempts', '<', 5)
            ->orderBy('created_at')
            ->limit(50)
            ->get()
            ->each(function (EventOutbox $outbox) use (&$published) {
                try {
                    $this->publishOne($outbox);
                    $published++;
                } catch (\Throwable $e) {
                    $outbox->markFailed($e->getMessage());
                    Log::error("[EnrollEase][Events] Failed to publish {$outbox->event_name}", [
                        'event_id' => $outbox->event_id,
                        'error'    => $e->getMessage(),
                    ]);
                }
            });

        return $published;
    }

    private function publishOne(EventOutbox $outbox): void
    {
        $body      = json_encode([
            'id'             => $outbox->event_id,
            'name'           => $outbox->event_name,
            'source_module'  => 'EnrollEase',
            'payload'        => $outbox->payload,
            'occurred_at'    => $outbox->created_at->toIso8601String(),
            'correlation_id' => $outbox->correlation_id,
            'schema_version' => $outbox->schema_version,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $timestamp = time();
        $nonce     = Str::random(32);
        $signature = hash_hmac('sha256', $timestamp . '.' . $nonce . '.' . $body, $this->secret);

        $response = Http::withHeaders([
            'X-DEORIS-Module'    => 'EnrollEase',
            'X-DEORIS-Timestamp' => (string) $timestamp,
            'X-DEORIS-Nonce'     => $nonce,
            'X-DEORIS-Signature' => $signature,
            'Accept'             => 'application/json',
            'Content-Type'       => 'application/json',
        ])
        ->timeout(10)
        ->withBody($body, 'application/json')
        ->post("{$this->portalUrl}/api/v1/events");

        if (! $response->successful()) {
            // 422 = DEORIS rejected the event (unknown name, bad payload) — don't retry
            if ($response->status() === 422) {
                $outbox->markCancelled("DEORIS rejected event: " . $response->body());
                Log::warning("[EnrollEase][Events] Event {$outbox->event_name} rejected by DEORIS (422) — cancelled.", [
                    'event_id' => $outbox->event_id,
                    'response' => $response->body(),
                ]);
                return;
            }
            throw new \RuntimeException(
                "DEORIS Event Hub returned {$response->status()}: " . $response->body()
            );
        }

        $outbox->markPublished();

        Log::info("[EnrollEase][Events] Published {$outbox->event_name}", [
            'event_id' => $outbox->event_id,
        ]);
    }

    private function sign(array $body): string
    {
        $encoded = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = time();
        $nonce     = Str::random(32);
        return hash_hmac('sha256', $timestamp . '.' . $nonce . '.' . $encoded, $this->secret);
    }
}
