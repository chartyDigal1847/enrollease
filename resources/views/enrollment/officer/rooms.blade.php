@extends('layouts.enrollment')
@section('title', 'Manage Rooms — Admission Officer')
@section('body-class', 'role-officer')

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div>
            <h1><i class="fa-solid fa-door-open"></i> Manage Rooms</h1>
            <p>Create classrooms, set capacity, and assign approved students.</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('officer.dashboard') }}" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
            <button class="btn btn-primary btn-sm" onclick="openModal('createRoomModal')">
                <i class="fa-solid fa-plus"></i> New Room
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success mb-6"><i class="fa-solid fa-circle-check"></i><div>{{ session('success') }}</div></div>
    @endif
    @if(session('error'))
    <div class="alert alert-error mb-6"><i class="fa-solid fa-triangle-exclamation"></i><div>{{ session('error') }}</div></div>
    @endif

    @forelse($rooms ?? [] as $room)
    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">
                <i class="fa-solid fa-door-open"></i>
                {{ $room->name }}
                @if($room->section)
                    <span style="font-weight:400;opacity:.8;">— {{ $room->section }}</span>
                @endif
                <span class="badge badge-verified" style="margin-left:.5rem;">Grade {{ $room->grade_level }}</span>
            </span>
            <div class="flex gap-2">
                <button class="btn-ghost-white" onclick="openModal('capacityModal{{ $room->id }}')">
                    <i class="fa-solid fa-sliders"></i> Capacity
                </button>
                <button class="btn-ghost-white" onclick="openModal('editRoomModal{{ $room->id }}')">
                    <i class="fa-solid fa-pen"></i> Edit
                </button>
                <form method="POST" action="{{ route('officer.rooms.destroy', $room->id) }}"
                      onsubmit="return confirm('Delete {{ $room->name }}? Students will be unassigned.')">
                    @csrf @method('DELETE')
                    <button class="btn-ghost-white" style="color:#fca5a5;">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">

            {{-- Capacity bars --}}
            @php
                $maleCount   = $room->students->where('gender','male')->count();
                $femaleCount = $room->students->where('gender','female')->count();
                $malePct     = $room->capacity_male   > 0 ? min(100, round($maleCount   / $room->capacity_male   * 100)) : 0;
                $femalePct   = $room->capacity_female > 0 ? min(100, round($femaleCount / $room->capacity_female * 100)) : 0;
            @endphp
            <div class="capacity-grid">
                <div>
                    <div class="cap-bar-header">
                        <span><i class="fa-solid fa-mars" style="color:var(--info);"></i> Male</span>
                        <span>{{ $maleCount }} / {{ $room->capacity_male ?: '∞' }}</span>
                    </div>
                    <div class="cap-bar-track">
                        <div class="cap-bar-fill male" style="width:{{ $malePct }}%;"></div>
                    </div>
                </div>
                <div>
                    <div class="cap-bar-header">
                        <span><i class="fa-solid fa-venus" style="color:#ec4899;"></i> Female</span>
                        <span>{{ $femaleCount }} / {{ $room->capacity_female ?: '∞' }}</span>
                    </div>
                    <div class="cap-bar-track">
                        <div class="cap-bar-fill female" style="width:{{ $femalePct }}%;"></div>
                    </div>
                </div>
            </div>

            {{-- Assign approved student --}}
            @php $gradeStudents = ($approvedStudents[$room->grade_level] ?? collect()); @endphp
            @if($gradeStudents->isNotEmpty())
            <form method="POST" action="{{ route('officer.rooms.assign', $room->id) }}" class="assign-row">
                @csrf
                <div class="form-group">
                    <label>Assign Approved Student</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">Select student…</option>
                        @foreach($gradeStudents as $s)
                            <option value="{{ $s->id }}">
                                {{ $s->student_name }}
                                @if($s->gender)
                                    ({{ ucfirst($s->gender) }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-user-plus"></i> Assign
                </button>
            </form>
            @endif

            {{-- Students in room --}}
            @if($room->students->isNotEmpty())
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Gender</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($room->students as $s)
                        <tr>
                            <td>
                                <strong>{{ $s->student_name }}</strong>
                                <span class="td-sub">{{ $s->email }}</span>
                            </td>
                            <td>{{ ucfirst($s->gender ?? '—') }}</td>
                            <td><span class="badge badge-{{ $s->status }}">{{ $s->status_label }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('officer.rooms.removeStudent', [$room->id, $s->id]) }}"
                                      onsubmit="return confirm('Remove {{ $s->student_name }} from this room?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-sm" style="color:var(--danger);" title="Remove">
                                        <i class="fa-solid fa-user-minus"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="empty-state" style="padding:20px 0;">
                <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
                <p>No students assigned yet.</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Edit Room Modal --}}
    <div class="modal-overlay" id="editRoomModal{{ $room->id }}">
        <div class="modal">
            <div class="modal-header">
                <span class="modal-title"><i class="fa-solid fa-pen"></i> Edit — {{ $room->name }}</span>
                <button class="modal-close" onclick="closeModal('editRoomModal{{ $room->id }}')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="{{ route('officer.rooms.update', $room->id) }}">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Room Name <span class="req">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $room->name }}" required>
                        </div>
                        <div class="form-group">
                            <label>Grade Level <span class="req">*</span></label>
                            <select name="grade_level" class="form-control" required>
                                @for($g=7;$g<=12;$g++)
                                    <option value="{{ $g }}" {{ $room->grade_level==$g?'selected':'' }}>Grade {{ $g }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Section</label>
                            <input type="text" name="section" class="form-control" value="{{ $room->section }}">
                        </div>
                        <div class="form-group">
                            <label>Adviser</label>
                            <input type="text" name="adviser" class="form-control" value="{{ $room->adviser }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('editRoomModal{{ $room->id }}')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Capacity Modal --}}
    <div class="modal-overlay" id="capacityModal{{ $room->id }}">
        <div class="modal">
            <div class="modal-header">
                <span class="modal-title"><i class="fa-solid fa-sliders"></i> Capacity — {{ $room->name }}</span>
                <button class="modal-close" onclick="closeModal('capacityModal{{ $room->id }}')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="{{ route('officer.rooms.capacity', $room->id) }}">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Male Capacity</label>
                            <input type="number" name="capacity_male" class="form-control" value="{{ $room->capacity_male }}" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Female Capacity</label>
                            <input type="number" name="capacity_female" class="form-control" value="{{ $room->capacity_female }}" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('capacityModal{{ $room->id }}')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Capacity</button>
                </div>
            </form>
        </div>
    </div>

    @empty
    <div class="card">
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-door-open"></i></div>
            <h3>No rooms yet</h3>
            <p>Create your first classroom to start assigning students.</p>
            <button class="btn btn-primary mt-4" onclick="openModal('createRoomModal')">
                <i class="fa-solid fa-plus"></i> Create Room
            </button>
        </div>
    </div>
    @endforelse

</div>

{{-- Create Room Modal --}}
<div class="modal-overlay" id="createRoomModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fa-solid fa-plus"></i> Create New Room</span>
            <button class="modal-close" onclick="closeModal('createRoomModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('officer.rooms.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Room Name <span class="req">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Room 101" required>
                    </div>
                    <div class="form-group">
                        <label>Grade Level <span class="req">*</span></label>
                        <select name="grade_level" class="form-control" required>
                            <option value="">Select…</option>
                            @for($g=7;$g<=12;$g++)
                                <option value="{{ $g }}">Grade {{ $g }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Section</label>
                        <input type="text" name="section" class="form-control" placeholder="e.g. Rizal">
                    </div>
                    <div class="form-group">
                        <label>Adviser</label>
                        <input type="text" name="adviser" class="form-control" placeholder="Teacher name">
                    </div>
                    <div class="form-group">
                        <label>Male Capacity</label>
                        <input type="number" name="capacity_male" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>Female Capacity</label>
                        <input type="number" name="capacity_female" class="form-control" value="0" min="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('createRoomModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Room</button>
            </div>
        </form>
    </div>
</div>

@endsection
