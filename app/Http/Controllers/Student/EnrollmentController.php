<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Enrollment;
use App\Services\PortalStudentLinker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Student — enroll and view status only.
 * Once submitted, the student can only view their application.
 * All processing is handled by the Admission Officer.
 */
class EnrollmentController extends Controller
{
    private function ssoStudent(): object
    {
        return (object) [
            'id'    => session('sso_id',    ''),
            'name'  => session('sso_name',  ''),
            'email' => session('sso_email', ''),
            'role'  => session('sso_role',  'student'),
        ];
    }

    /** GET /student/dashboard */
    public function dashboard()
    {
        $student    = $this->ssoStudent();
        $enrollment = $student->email
            ? Enrollment::with('room')->where('email', $student->email)->latest()->first()
            : null;

        return view('enrollment.student.dashboard', [
            'student'    => $student,
            'enrollment' => $enrollment,
        ]);
    }

    /** GET /student/enrollment/apply */
    public function create()
    {
        $student = $this->ssoStudent();

        $active = $student->email
            ? Enrollment::where('email', $student->email)
                ->whereNotIn('status', [Enrollment::STATUS_CANCELLED, Enrollment::STATUS_REJECTED])
                ->first()
            : null;

        if ($active) {
            return redirect()->route('student.enrollment.status')
                ->with('info', 'You already have an enrollment application. Contact the admission office if you need changes.');
        }

        return view('enrollment.student.create', ['student' => $student]);
    }

    /** POST /student/enrollment/apply */
    public function store(Request $request)
    {
        $student = $this->ssoStudent();

        if (! $student->email) {
            return back()->with('error', 'Session expired. Please log in again.');
        }

        $existing = Enrollment::where('email', $student->email)
            ->whereNotIn('status', [Enrollment::STATUS_CANCELLED, Enrollment::STATUS_REJECTED])
            ->first();

        if ($existing) {
            return redirect()->route('student.enrollment.status')
                ->with('info', 'You already have an active enrollment application.');
        }

        $data = $request->validate([
            'grade_level'           => 'required|integer|between:1,12',
            'gender'                => 'required|in:male,female',
            'date_of_birth'         => 'required|date|before:today',
            'contact_number'        => 'required|string|max:20',
            'address'               => 'required|string|max:300',
            'lrn'                   => 'nullable|string|size:12',
            'previous_school'       => 'nullable|string|max:150',
            'last_grade_completed'  => 'nullable|integer|between:1,12',
            'guardian_name'         => 'required|string|max:150',
            'guardian_relationship' => 'required|string|max:50',
            'guardian_contact'      => 'required|string|max:20',
            'guardian_occupation'   => 'nullable|string|max:100',
        ]);

        $nameParts  = explode(' ', trim($student->name));
        $lastName   = array_pop($nameParts) ?? '';
        $firstName  = array_shift($nameParts) ?? '';
        $middleName = implode(' ', $nameParts);

        try {
            $studentRecord = PortalStudentLinker::resolveOrCreateFromSession();

            $enrollment = Enrollment::create([
                'student_id'            => $studentRecord?->id,
                'student_name'          => $student->name,
                'first_name'            => $firstName,
                'last_name'             => $lastName,
                'middle_name'           => $middleName,
                'email'                 => $student->email,
                'gender'                => $data['gender'],
                'date_of_birth'         => $data['date_of_birth'],
                'contact_number'        => $data['contact_number'],
                'address'               => $data['address'],
                'lrn'                   => $data['lrn'] ?? null,
                'grade_level'           => $data['grade_level'],
                'school_year'           => date('Y') . '–' . (date('Y') + 1),
                'previous_school'       => $data['previous_school'] ?? null,
                'last_grade_completed'  => $data['last_grade_completed'] ?? null,
                'guardian_name'         => $data['guardian_name'],
                'guardian_relationship' => $data['guardian_relationship'],
                'guardian_contact'      => $data['guardian_contact'],
                'guardian_occupation'   => $data['guardian_occupation'] ?? null,
                'status'                => Enrollment::STATUS_PENDING,
            ]);

            \App\Models\EnrollmentStatusLog::record(
                enrollmentId: $enrollment->id,
                fromStatus:   null,
                toStatus:     Enrollment::STATUS_PENDING,
                remarks:      'Enrollment submitted by student.',
                role:         'student',
                actorId:      $student->id,
                actorName:    $student->name,
            );

            ActivityLog::log('enrollment.submitted', $enrollment, [
                'grade_level' => $enrollment->grade_level,
                'school_year' => $enrollment->school_year,
            ]);

            return redirect()->route('student.enrollment.status')
                ->with('success', 'Enrollment #' . str_pad($enrollment->id, 4, '0', STR_PAD_LEFT)
                    . ' submitted. Your application is now pending review by the admission office.');
        } catch (\Exception $e) {
            Log::error('[EnrollEase] Enrollment store error', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Something went wrong. Please try again.');
        }
    }

    /** GET /student/enrollment/status */
    public function status()
    {
        $student    = $this->ssoStudent();
        $enrollment = $student->email
            ? Enrollment::with(['room', 'statusLogs'])->where('email', $student->email)->latest()->first()
            : null;

        return view('enrollment.student.status', [
            'student'    => $student,
            'enrollment' => $enrollment,
        ]);
    }
}
