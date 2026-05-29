<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /** GET /admin/students */
    public function index(Request $request)
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
