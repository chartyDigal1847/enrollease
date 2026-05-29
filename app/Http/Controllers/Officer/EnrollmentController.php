<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Enrollment;
use App\Models\Room;
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
            'verifiedCount'      => Enrollment::whereIn('status', [Enrollment::STATUS_REVIEWING])->count(),
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
    public function verify($id)
    {
        $enrollment = Enrollment::findOrFail($id);

        if ($enrollment->status !== Enrollment::STATUS_PENDING) {
            return back()->with('error', 'Only pending enrollments can be verified.');
        }

        $enrollment->transitionTo(Enrollment::STATUS_REVIEWING);

        ActivityLog::log('enrollment.verified', $enrollment);

        return back()->with('success', "Enrollment #{$id} is now under review.");
    }

    /** PATCH /officer/enrollments/{id}/approve — reviewing/pending → approved */
    public function approve($id)
    {
        $enrollment = Enrollment::findOrFail($id);

        if (! in_array($enrollment->status, [Enrollment::STATUS_PENDING, Enrollment::STATUS_REVIEWING])) {
            return back()->with('error', 'Only pending or reviewing enrollments can be approved.');
        }

        $enrollment->transitionTo(Enrollment::STATUS_APPROVED);

        \App\Jobs\PublishPendingEvents::dispatchSync();
        ActivityLog::log('enrollment.approved', $enrollment);

        return back()->with('success', "Enrollment #{$id} approved.");
    }

    /** PATCH /officer/enrollments/{id}/reject */
    public function reject($id)
    {
        $enrollment = Enrollment::findOrFail($id);

        if (in_array($enrollment->status, [Enrollment::STATUS_ENROLLED, Enrollment::STATUS_REJECTED, Enrollment::STATUS_CANCELLED])) {
            return back()->with('error', 'This enrollment cannot be rejected at its current status.');
        }

        $enrollment->transitionTo(Enrollment::STATUS_REJECTED);

        \App\Jobs\PublishPendingEvents::dispatchSync();
        ActivityLog::log('enrollment.rejected', $enrollment);

        return back()->with('success', "Enrollment #{$id} rejected.");
    }

    /** PATCH /officer/enrollments/{id}/update-status */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status'  => 'required|in:' . implode(',', Enrollment::STATUSES),
            'remarks' => 'nullable|string|max:500',
        ]);

        $enrollment = Enrollment::findOrFail($id);
        $newStatus  = $request->status;

        $enrollment->transitionTo($newStatus, $request->remarks);

        if ($newStatus !== Enrollment::STATUS_PENDING && $newStatus !== Enrollment::STATUS_REVIEWING) {
            \App\Jobs\PublishPendingEvents::dispatchSync();
        }

        ActivityLog::log("enrollment.status.{$newStatus}", $enrollment, ['remarks' => $request->remarks]);

        return back()->with('success', 'Status updated to ' . ucfirst($newStatus) . '.');
    }

    /** GET /officer/documents/{id}/{type} */
    public function viewDocument($id, $type)
    {
        $enrollment = Enrollment::findOrFail($id);

        $documentMap = [
            'psa'    => $enrollment->psa_path,
            'photo'  => $enrollment->photo_path,
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
