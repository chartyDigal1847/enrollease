<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Enrollment;
use App\Models\Room;
use App\Services\EntryEaseApplicantDocuments;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Admission Officer — full enrollment processing.
 * Verify, approve, reject, update status, manage rooms.
 */
class EnrollmentController extends Controller
{
    public function __construct() {}

    /** GET /officer/dashboard */
    public function dashboard()
    {
        return view('enrollment.officer.dashboard', [
            'role'               => 'officer',
            'totalEnrollments'   => Enrollment::count(),
            'pendingCount'       => Enrollment::where('status', Enrollment::STATUS_PENDING)->count(),
            'verifiedCount'      => Enrollment::where('status', Enrollment::STATUS_REVIEWING)->count(),
            'reviewingCount'     => Enrollment::where('status', Enrollment::STATUS_REVIEWING)->count(),
            'approvedCount'      => Enrollment::where('status', Enrollment::STATUS_APPROVED)->count(),
            'enrolledCount'      => Enrollment::where('status', Enrollment::STATUS_ENROLLED)->count(),
            'processedToday'     => Enrollment::whereIn('status', [
                                        Enrollment::STATUS_APPROVED,
                                        Enrollment::STATUS_REJECTED,
                                    ])->whereDate('updated_at', Carbon::today())->count(),
            'pendingEnrollments' => Enrollment::where('status', Enrollment::STATUS_PENDING)
                                        ->latest()->limit(10)->get(),
        ]);
    }

    /** GET /officer/enrollments */
    public function index(Request $request)
    {
        $query = Enrollment::with('room')->latest();

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($b) =>
                $b->where('student_name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
            );
        }

        return view('enrollment.officer.enrollments', [
            'role'        => 'officer',
            'enrollments' => $query->paginate(20),
        ]);
    }

    /** GET /officer/enrollments/{id} */
    public function show($id)
    {
        return view('enrollment.officer.show', [
            'role'       => 'officer',
            'enrollment' => Enrollment::with(['room', 'statusLogs'])->findOrFail($id),
        ]);
    }

    /** PATCH /officer/enrollments/{id}/verify — pending → reviewing */
    public function verify(Request $request, $id)
    {
        $enrollment = Enrollment::findOrFail($id);

        if ($enrollment->status !== Enrollment::STATUS_PENDING) {
            return back()->with('error', 'Only pending enrollments can be verified.');
        }

        $rules = collect(Enrollment::VERIFICATION_CHECKS)
            ->mapWithKeys(fn ($label, $key) => ["verification_checks.{$key}" => 'accepted'])
            ->all();

        $request->validate($rules, [
            'verification_checks.*.accepted' => 'Complete all verification checklist items before verifying this enrollment.',
        ]);

        $enrollment->update([
            'verification_checks' => collect(Enrollment::VERIFICATION_CHECKS)
                ->mapWithKeys(fn ($label, $key) => [$key => true])
                ->all(),
            'verified_at' => now(),
            'verified_by' => (string) session('sso_id', session('sso_name', 'Admission Officer')),
        ]);

        try {
            $enrollment->transitionTo(Enrollment::STATUS_REVIEWING);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        ActivityLog::log('enrollment.verified', $enrollment);

        return back()->with('success', "Enrollment #{$id} is now under review.");
    }

    /** PATCH /officer/enrollments/{id}/approve — reviewing → approved */
    public function approve($id)
    {
        $enrollment = Enrollment::findOrFail($id);

        if ($enrollment->status !== Enrollment::STATUS_REVIEWING) {
            return back()->with('error', 'Only enrollments under review can be approved. Verify the application first.');
        }

        try {
            $enrollment->transitionTo(Enrollment::STATUS_APPROVED);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        \App\Jobs\PublishPendingEvents::dispatchSync();
        ActivityLog::log('enrollment.approved', $enrollment);

        return back()->with('success', "Enrollment #{$id} approved.");
    }

    /** PATCH /officer/enrollments/{id}/reject */
    public function reject($id)
    {
        $enrollment = Enrollment::findOrFail($id);

        if (! $enrollment->canTransitionTo(Enrollment::STATUS_REJECTED)) {
            return back()->with('error', 'This enrollment cannot be rejected at its current status.');
        }

        try {
            $enrollment->transitionTo(Enrollment::STATUS_REJECTED);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        \App\Jobs\PublishPendingEvents::dispatchSync();
        ActivityLog::log('enrollment.rejected', $enrollment);

        return back()->with('success', "Enrollment #{$id} rejected.");
    }

    /** PATCH /officer/enrollments/{id}/update-status */
    public function updateStatus(Request $request, $id)
    {
        $enrollment = Enrollment::findOrFail($id);
        $allowed    = $enrollment->nextStatuses();

        if (empty($allowed)) {
            return back()->with('error', 'No further status changes are allowed for this enrollment.');
        }

        $request->validate([
            'status'  => 'required|in:' . implode(',', $allowed),
            'remarks' => 'nullable|string|max:500',
        ]);

        $newStatus = $request->status;

        if ($enrollment->status === Enrollment::STATUS_PENDING && $newStatus === Enrollment::STATUS_REVIEWING) {
            return back()->with('error', 'Use the verification checklist before moving this enrollment under review.');
        }

        try {
            $enrollment->transitionTo($newStatus, $request->remarks);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($newStatus !== Enrollment::STATUS_PENDING && $newStatus !== Enrollment::STATUS_REVIEWING) {
            \App\Jobs\PublishPendingEvents::dispatchSync();
        }

        ActivityLog::log("enrollment.status.{$newStatus}", $enrollment, ['remarks' => $request->remarks]);

        return back()->with('success', 'Status updated to ' . ucfirst($newStatus) . '.');
    }

    /** GET /officer/documents/{id}/{type} */
    public function viewDocument($id, $type, EntryEaseApplicantDocuments $entryEaseDocuments)
    {
        $enrollment = Enrollment::with('student')->findOrFail($id);

        if (in_array($type, ['psa', 'photo'], true)) {
            return $entryEaseDocuments->stream($enrollment, $type);
        }

        $documentMap = [
            'report' => $enrollment->report_card_path,
        ];

        if (! isset($documentMap[$type]) || ! $documentMap[$type]) {
            abort(404, 'Document not found.');
        }

        $filePath = storage_path('app/public/' . $documentMap[$type]);

        if (! file_exists($filePath)) {
            abort(404, 'File not found on disk.');
        }

        $mimeType = mime_content_type($filePath);

        return response()->file($filePath, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
        ]);
    }
}
