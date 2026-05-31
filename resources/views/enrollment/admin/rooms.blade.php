@extends('layouts.enrollment')
@section('title', 'Room Overview — Admin')
@section('body-class', 'role-admin')

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div>
            <h1><i class="fa-solid fa-door-open"></i> Room Overview</h1>
            <p>Read-only view of all classrooms and student assignments.</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

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
            <span class="record-count">{{ $room->students_count }} student{{ $room->students_count !== 1 ? 's' : '' }}</span>
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

            @if($room->adviser)
            <p class="text-sm" style="color:var(--text-muted);margin-bottom:1rem;">
                <i class="fa-solid fa-chalkboard-user"></i> Adviser: <strong>{{ $room->adviser }}</strong>
            </p>
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

    @empty
    <div class="card">
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-door-open"></i></div>
            <h3>No rooms created yet</h3>
            <p>Rooms are managed by the Admission Officer.</p>
        </div>
    </div>
    @endforelse

</div>
@endsection
