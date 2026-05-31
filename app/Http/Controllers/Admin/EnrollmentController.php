<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Room;
use Illuminate\Http\Request;

/**
 * Admin — monitor only.
 * No write actions. Admins can view everything but cannot change anything.
 */
class EnrollmentController extends Controller
{
    /** GET /admin/dashboard */
    public function dashboard()
    {
        return view('enrollment.admin.dashboard', [
            'role'              => 'admin',
            'totalEnrollments'  => Enrollment::count(),
            'pendingCount'      => Enrollment::where('status', 'pending')->count(),
            'verifiedCount'     => Enrollment::where('status', Enrollment::STATUS_REVIEWING)->count(),
            'reviewingCount'    => Enrollment::where('status', Enrollment::STATUS_REVIEWING)->count(),
            'approvedCount'     => Enrollment::where('status', 'approved')->count(),
            'enrolledCount'     => Enrollment::where('status', 'enrolled')->count(),
            'rejectedCount'     => Enrollment::where('status', 'rejected')->count(),
            'roomCount'         => Room::count(),
            'recentEnrollments' => Enrollment::with('room')->latest()->limit(10)->get(),
        ]);
    }

    /** GET /admin/enrollments */
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
            $query->where(function ($q) use ($request) {
                $q->where('student_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        return view('enrollment.admin.enrollments', [
            'role'        => 'admin',
            'enrollments' => $query->paginate(20),
        ]);
    }

    /** GET /admin/enrollments/{id} */
    public function show($id)
    {
        return view('enrollment.admin.show', [
            'role'       => 'admin',
            'enrollment' => Enrollment::with('room')->findOrFail($id),
        ]);
    }

    /** GET /admin/rooms — read-only room overview */
    public function rooms()
    {
        return view('enrollment.admin.rooms', [
            'role'  => 'admin',
            'rooms' => Room::with('students')->withCount('students')->get(),
        ]);
    }

    /** GET /admin/students */
    public function students(Request $request)
    {
        $query = Enrollment::with('room')->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('student_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        return view('enrollment.admin.students', [
            'role'        => 'admin',
            'enrollments' => $query->paginate(20),
        ]);
    }
}
