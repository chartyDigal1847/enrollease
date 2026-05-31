<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /** GET /admin/rooms */
    public function index()
    {
        return view('enrollment.admin.rooms', [
            'role'             => 'admin',
            'rooms'            => Room::with('students')->withCount('students')->get(),
            'approvedStudents' => Enrollment::where('status', 'approved')
                                            ->whereNull('room_id')
                                            ->get()
                                            ->groupBy('grade_level'), // grouped by grade
        ]);
    }

    /** POST /admin/rooms */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'grade_level' => 'required|integer|between:7,12',
        ]);

        Room::create($request->only('name', 'grade_level', 'section', 'adviser', 'capacity_male', 'capacity_female'));

        return redirect()->route('admin.rooms.index')->with('success', 'Room created successfully.');
    }

    /** PUT /admin/rooms/{id} */
    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);
        $room->update($request->only('name', 'grade_level', 'section', 'adviser'));

        return redirect()->route('admin.rooms.index')->with('success', 'Room updated.');
    }

    /** DELETE /admin/rooms/{id} */
    public function destroy($id)
    {
        Room::findOrFail($id)->delete();

        return redirect()->route('admin.rooms.index')->with('success', 'Room deleted.');
    }

    /** POST /admin/rooms/{id}/assign */
    public function assign(Request $request, $id)
    {
        $request->validate(['student_id' => 'required|exists:enrollments,id']);

        $room       = Room::findOrFail($id);
        $enrollment = Enrollment::findOrFail($request->student_id);

        // ── Grade level validation ────────────────────────────────────────────
        if ((int) $enrollment->grade_level !== (int) $room->grade_level) {
            return back()->with(
                'error',
                "Cannot assign {$enrollment->student_name} (Grade {$enrollment->grade_level}) 
                 to {$room->name} which is a Grade {$room->grade_level} room."
            );
        }

        // ── Capacity validation ───────────────────────────────────────────────
        $gender   = strtolower($enrollment->gender ?? '');
        $enrolled = $room->students()->where('gender', $gender)->count();

        if ($gender === 'male' && $room->capacity_male > 0 && $enrolled >= $room->capacity_male) {
            return back()->with('error', "This room has reached its male capacity ({$room->capacity_male}).");
        }

        if ($gender === 'female' && $room->capacity_female > 0 && $enrolled >= $room->capacity_female) {
            return back()->with('error', "This room has reached its female capacity ({$room->capacity_female}).");
        }

        $enrollment->update(['room_id' => $room->id]);
        $enrollment->transitionTo(Enrollment::STATUS_ENROLLED);

        \App\Jobs\PublishPendingEvents::dispatchSync();

        return redirect()->route('admin.rooms.index')->with('success', 'Student assigned to room.');
    }

    /** PATCH /admin/rooms/{id}/capacity */
    public function capacity(Request $request, $id)
    {
        $request->validate([
            'capacity_male'   => 'required|integer|min:0',
            'capacity_female' => 'required|integer|min:0',
        ]);

        Room::findOrFail($id)->update($request->only('capacity_male', 'capacity_female'));

        return redirect()->route('admin.rooms.index')->with('success', 'Room capacity updated.');
    }

    /** DELETE /admin/rooms/{id}/students/{enrollmentId} */
    public function removeStudent($id, $enrollmentId)
    {
        $room       = Room::findOrFail($id);
        $enrollment = Enrollment::findOrFail($enrollmentId);

        if ($enrollment->room_id !== $room->id) {
            return back()->with('error', 'Student is not assigned to this room.');
        }

        $studentName = $enrollment->student_name;
        $enrollment->update(['room_id' => null]);
        $enrollment->transitionTo(Enrollment::STATUS_APPROVED);

        return redirect()->route('admin.rooms.index')->with('success', "Student $studentName removed from room $room->name.");
    }
}
