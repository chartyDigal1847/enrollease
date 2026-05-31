<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\ActivityLog;
use App\Models\Enrollment;
use App\Models\EnrollmentStatusLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * GET  /api/v1/enrollments
 * GET  /api/v1/enrollments/{id}
 * POST /api/v1/enrollments
 * PUT  /api/v1/enrollments/{id}
 * DELETE /api/v1/enrollments/{id}
 */
class EnrollmentApiController extends Controller
{
    public function __construct() {}

    /** GET /api/v1/enrollments */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Enrollment::with(['room', 'academicTerm'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }
        if ($request->filled('school_year')) {
            $query->where('school_year', $request->school_year);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($b) =>
                $b->where('student_name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('lrn', 'like', "%{$q}%")
            );
        }

        $perPage = min((int) $request->integer('per_page', 15), 100);

        return EnrollmentResource::collection($query->paginate($perPage));
    }

    /** GET /api/v1/enrollments/{id} */
    public function show(int $id): EnrollmentResource
    {
        $enrollment = Enrollment::with(['room', 'academicTerm', 'statusLogs'])->findOrFail($id);
        return new EnrollmentResource($enrollment);
    }

    /** POST /api/v1/enrollments */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'grade_level'  => 'required|integer|between:6,12',
            'school_year'  => 'nullable|string|max:20',
            'student_name' => 'required|string|max:200',
            'email'        => 'required|email|max:150',
            'gender'       => 'nullable|in:male,female',
            'lrn'          => 'nullable|string|max:12',
        ]);

        // Prevent duplicate active enrollment
        $existing = Enrollment::where('email', $data['email'])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'error'   => 'duplicate_enrollment',
                'message' => 'An active enrollment already exists for this student.',
            ], 422);
        }

        $enrollment = Enrollment::create(array_merge($data, [
            'status'      => Enrollment::STATUS_PENDING,
            'school_year' => $data['school_year'] ?? date('Y') . '–' . (date('Y') + 1),
        ]));

        ActivityLog::log('enrollment.submitted', $enrollment);

        return response()->json([
            'success' => true,
            'data'    => new EnrollmentResource($enrollment),
            'message' => 'Enrollment submitted successfully.',
        ], 201);
    }

    /** PUT /api/v1/enrollments/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $enrollment = Enrollment::findOrFail($id);

        // Only officer/admin can update via API
        $role = session('sso_role', 'student');
        if (! in_array($role, ['officer', 'admin'])) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $data = $request->validate([
            'status'      => 'sometimes|in:' . implode(',', $enrollment->nextStatuses()),
            'remarks'     => 'nullable|string|max:500',
            'room_id'     => 'nullable|exists:rooms,id',
            'grade_level' => 'sometimes|integer|between:6,12',
        ]);

        $oldStatus = $enrollment->status;

        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            if (! $enrollment->canTransitionTo($data['status'])) {
                return response()->json([
                    'success' => false,
                    'error'   => 'invalid_transition',
                    'message' => "Cannot transition enrollment from '{$oldStatus}' to '{$data['status']}'.",
                ], 422);
            }

            if (array_key_exists('room_id', $data)) {
                $enrollment->update(['room_id' => $data['room_id']]);
                unset($data['room_id']);
            }

            try {
                $enrollment->transitionTo($data['status'], $data['remarks'] ?? null);
            } catch (\InvalidArgumentException $e) {
                return response()->json([
                    'success' => false,
                    'error'   => 'invalid_transition',
                    'message' => $e->getMessage(),
                ], 422);
            }

            if (! in_array($data['status'], [Enrollment::STATUS_PENDING, Enrollment::STATUS_REVIEWING], true)) {
                \App\Jobs\PublishPendingEvents::dispatchSync();
            }

            unset($data['status'], $data['remarks']);
        }

        if (! empty($data)) {
            $enrollment->update($data);
        }

        return response()->json([
            'success' => true,
            'data'    => new EnrollmentResource($enrollment->fresh(['room', 'academicTerm'])),
            'message' => 'Enrollment updated.',
        ]);
    }

    /** DELETE /api/v1/enrollments/{id} */
    public function destroy(int $id): JsonResponse
    {
        $enrollment = Enrollment::findOrFail($id);

        $role = session('sso_role', 'student');
        if ($role !== 'admin') {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        ActivityLog::log('enrollment.deleted', $enrollment);
        $enrollment->delete();

        return response()->json(['success' => true, 'message' => 'Enrollment deleted.']);
    }

    /** GET /api/v1/enrollments/{id}/status-history */
    public function statusHistory(int $id): JsonResponse
    {
        $enrollment = Enrollment::findOrFail($id);
        $logs = EnrollmentStatusLog::where('enrollment_id', $id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($l) => [
                'from'             => $l->from_status,
                'to'               => $l->to_status,
                'changed_by_role'  => $l->changed_by_role,
                'changed_by_name'  => $l->changed_by_name,
                'remarks'          => $l->remarks,
                'at'               => $l->created_at?->toIso8601String(),
            ]);

        return response()->json(['success' => true, 'data' => $logs]);
    }

    /** GET /api/v1/enrollments/{id}/sync-status */
    public function syncStatus(int $id): JsonResponse
    {
        $enrollment = Enrollment::findOrFail($id);

        // Get status history with event sync information
        $statusLogs = EnrollmentStatusLog::where('enrollment_id', $id)
            ->orderByDesc('created_at')
            ->get();

        // Get related events from outbox
        $events = EventOutbox::where('payload->user_id', $enrollment->student_id)
            ->whereIn('payload->enrollment_id', [$enrollment->id])
            ->orderByDesc('created_at')
            ->get();

        $syncData = [
            'enrollment' => [
                'id'         => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'email'      => $enrollment->email,
                'status'     => $enrollment->status,
            ],
            'status_history' => $statusLogs->map(fn ($l) => [
                'status_change' => [
                    'from'           => $l->from_status,
                    'to'             => $l->to_status,
                    'changed_by'     => [
                        'role' => $l->changed_by_role,
                        'name' => $l->changed_by_name,
                    ],
                    'remarks'        => $l->remarks,
                    'at'             => $l->created_at?->toIso8601String(),
                ],
            ])->toArray(),
            'deoris_sync_events' => $events->map(fn ($e) => [
                'event_id'      => $e->event_id,
                'event_name'    => $e->event_name,
                'status'        => $e->status,
                'attempts'      => $e->attempts,
                'payload'       => $e->payload,
                'created_at'    => $e->created_at?->toIso8601String(),
                'published_at'  => $e->published_at?->toIso8601String(),
                'last_error'    => $e->last_error,
            ])->toArray(),
            'sync_summary' => [
                'total_events'    => $events->count(),
                'published'       => $events->where('status', 'published')->count(),
                'pending'         => $events->where('status', 'pending')->count(),
                'failed'          => $events->where('status', 'failed')->count(),
                'enrolled_event'  => $events->firstWhere('event_name', 'StudentEnrolled') ? true : false,
            ],
        ];

        return response()->json(['success' => true, 'data' => $syncData]);
    }
}
