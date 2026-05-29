<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Enrollment extends Model
{
    use HasFactory;

    // ── Status constants ────────────────────────────────────────────────────
    const STATUS_PENDING    = 'pending';
    const STATUS_REVIEWING  = 'reviewing';
    const STATUS_APPROVED   = 'approved';
    const STATUS_REJECTED   = 'rejected';
    const STATUS_ENROLLED   = 'enrolled';
    const STATUS_CANCELLED  = 'cancelled';

    const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REVIEWING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_ENROLLED,
        self::STATUS_CANCELLED,
    ];

    const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REVIEWING,
        self::STATUS_APPROVED,
        self::STATUS_ENROLLED,
    ];

    protected $fillable = [
        'student_id',
        'academic_term_id',
        'student_name',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'gender',
        'nationality',
        'email',
        'contact_number',
        'address',

        'grade_level',
        'school_year',
        'previous_school',
        'last_grade_completed',
        'average_grade',
        'lrn',

        'guardian_name',
        'guardian_relationship',
        'guardian_contact',
        'guardian_email',
        'guardian_occupation',

        'psa_path',
        'photo_path',
        'report_card_path',

        'room_id',
        'status',
        'remarks',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    // ── Relationships ───────────────────────────────────────────────────────

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /** Alias: section = room in this implementation */
    public function section()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function academicTerm()
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(EnrollmentStatusLog::class)->latest();
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES);
    }

    public function canBeProcessedBy(string $role): bool
    {
        return match ($role) {
            'officer' => in_array($this->status, [self::STATUS_PENDING, self::STATUS_REVIEWING]),
            'admin'   => false, // admin is read-only
            default   => false,
        };
    }

    /**
     * Transition status and record the change in the audit log.
     * Publishes corresponding event to DEORIS via EventPublisher.
     */
    public function transitionTo(string $newStatus, ?string $remarks = null): void
    {
        $old = $this->status;
        $this->update(['status' => $newStatus, 'remarks' => $remarks ?? $this->remarks]);

        // App-level log (DB trigger also fires for DB-level audit)
        EnrollmentStatusLog::record(
            enrollmentId: $this->id,
            fromStatus:   $old,
            toStatus:     $newStatus,
            remarks:      $remarks,
        );

        ActivityLog::log("enrollment.status.{$newStatus}", $this, [
            'from' => $old,
            'to'   => $newStatus,
        ]);

        // Publish event to DEORIS
        $publisher = app(\App\Services\EventPublisher::class);

        match ($newStatus) {
            self::STATUS_ENROLLED   => $publisher->studentEnrolled($this),
            self::STATUS_APPROVED   => $publisher->enrollmentApproved($this),
            self::STATUS_REJECTED   => $publisher->enrollmentRejected($this),
            self::STATUS_CANCELLED  => $publisher->enrollmentCancelled($this),
            default                 => null,
        };
    }
}
