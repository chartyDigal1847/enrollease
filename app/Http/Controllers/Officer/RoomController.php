<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Enrollment;
use App\Models\Room;
use Illuminate\Http\Request;

/**
 * Admission Officer — room / section management.
 */
class RoomController extends Controller
{
    public function __construct() {}

    /** GET /officer/rooms */
    public function index()
    {
        return view('enrollment.officer.rooms', [
            'role'             => 'officer',
            'rooms'            => Room::with('students')->withCount('students')->get(),
            'approvedStudents' => Enrollment::where('status', Enrollment::STATUS_APPROVED)
                                            ->whereNull('room_id')
                                            ->get()
                                            ->groupBy('grade_level'),
        ]);
    }

    /** POST /officer/rooms */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'grade_level' => 'required|integer|between:6,12',
        ]);

        $room = Room::create($request->only('name', 'grade_level', 'section', 'adviser', 'capacity_male', 'capacity_female'));

        ActivityLog::log('room.created', $room);

        return redirect()->route('officer.rooms.index')->with('success', 'Room created successfully.');
    }

    /** PUT /officer/rooms/{id} */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'grade_level' => 'required|integer|between:6,12',
        ]);

        $room = Room::findOrFail($id);
        $room->update($request->only('name', 'grade_level', 'section', 'adviser'));

        ActivityLog::log('room.updated', $room);

        return redirect()->route('officer.rooms.index')->with('success', 'Room updated.');
    }

    /** DELETE /officer/rooms/{id} */
    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        ActivityLog::log('room.deleted', $room, ['name' => $room->name]);
        $room->delete();

        return redirect()->route('officer.rooms.index')->with('success', 'Room deleted.');
    }

    /** POST /officer/rooms/{id}/assign */
    public function assign(Request $request, $id)
    {
        $request->validate(['student_id' => 'required|exists:enrollments,id']);

        $room       = Room::findOrFail($id);
        $enrollment = Enrollment::findOrFail($request->student_id);

        if ((int) $enrollment->grade_level !== (int) $room->grade_level) {
            return back()->with('error',
                "Cannot assign {$enrollment->student_name} (Grade {$enrollment->grade_level}) "
                . "to {$room->name} which is a Grade {$room->grade_level} room."
            );
        }

        // Capacity check — only enforce if gender is known and capacity is set
        $gender   = strtolower($enrollment->gender ?? '');
        $occupied = $gender ? $room->students()->where('gender', $gender)->count() : 0;

        if ($gender === 'male' && $room->capacity_male > 0 && $occupied >= $room->capacity_male) {
            return back()->with('error', "This room has reached its male capacity ({$room->capacity_male}).");
        }
        if ($gender === 'female' && $room->capacity_female > 0 && $occupied >= $room->capacity_female) {
            return back()->with('error', "This room has reached its female capacity ({$room->capacity_female}).");
        }

        if ($enrollment->status !== Enrollment::STATUS_APPROVED) {
            return back()->with('error', 'Only approved enrollments can be assigned to a room.');
        }

        // Assign room and mark enrolled
        $enrollment->update(['room_id' => $room->id]);

        try {
            $enrollment->transitionTo(Enrollment::STATUS_ENROLLED);
        } catch (\InvalidArgumentException $e) {
            $enrollment->update(['room_id' => null]);

            return back()->with('error', $e->getMessage());
        }

        // Reload with room relationship for event payload
        $enrollment->refresh();
        $enrollment->load('room');

        \App\Jobs\PublishPendingEvents::dispatchSync();

        ActivityLog::log('enrollment.section_assigned', $enrollment, [
            'room_id'   => $room->id,
            'room_name' => $room->name,
        ]);

        return redirect()->route('officer.rooms.index')
            ->with('success', "{$enrollment->student_name} assigned to {$room->name} and marked as enrolled.");
    }

    /** PATCH /officer/rooms/{id}/capacity */
    public function capacity(Request $request, $id)
    {
        $request->validate([
            'capacity_male'   => 'required|integer|min:0',
            'capacity_female' => 'required|integer|min:0',
        ]);

        Room::findOrFail($id)->update($request->only('capacity_male', 'capacity_female'));

        return redirect()->route('officer.rooms.index')->with('success', 'Room capacity updated.');
    }

    /** DELETE /officer/rooms/{id}/students/{enrollmentId} */
    public function removeStudent($id, $enrollmentId)
    {
        $room       = Room::findOrFail($id);
        $enrollment = Enrollment::findOrFail($enrollmentId);

        if ((int) $enrollment->room_id !== (int) $room->id) {
            return back()->with('error', 'Student is not assigned to this room.');
        }

        $name = $enrollment->student_name;
        $enrollment->update(['room_id' => null]);

        try {
            $enrollment->transitionTo(Enrollment::STATUS_APPROVED);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        ActivityLog::log('enrollment.section_removed', $enrollment, ['room_name' => $room->name]);

        return redirect()->route('officer.rooms.index')
            ->with('success', "{$name} removed from {$room->name}.");
    }
}
