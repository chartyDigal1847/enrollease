@extends('layouts.enrollment')
@section('title', 'Student Records — Admin')
@section('body-class', 'role-admin')

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div>
            <h1><i class="fa-solid fa-users"></i> Student Records</h1>
            <p>Read-only view of all students and their enrollment status.</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.students') }}" class="filter-bar">
        <div class="form-group">
            <label>Search</label>
            <input type="text" name="search" class="form-control" placeholder="Name or email…" value="{{ request('search') }}">
        </div>
        <div class="form-group">
            <label>Grade Level</label>
            <select name="grade_level" class="form-control">
                <option value="">All Grades</option>
                @for($g = 7; $g <= 12; $g++)
                    <option value="{{ $g }}" {{ request('grade_level') == $g ? 'selected' : '' }}>Grade {{ $g }}</option>
                @endfor
            </select>
        </div>
        <div class="filter-bar-actions">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
            <a href="{{ route('admin.students') }}" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-users"></i> All Students</span>
            @if(isset($enrollments))
                <span class="record-count">{{ $enrollments->total() }} students</span>
            @endif
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Grade</th>
                        <th>Gender</th>
                        <th>Room</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrollments ?? [] as $e)
                    <tr>
                        <td class="text-muted fw-600">#{{ str_pad($e->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td>
                            <strong>{{ $e->student_name }}</strong>
                            <span class="td-sub">{{ $e->email }}</span>
                        </td>
                        <td>Grade {{ $e->grade_level }}</td>
                        <td>{{ ucfirst($e->gender ?? '—') }}</td>
                        <td>{{ $e->room->name ?? '—' }}</td>
                        <td><span class="badge badge-{{ $e->status }}">{{ $e->status_label }}</span></td>
                        <td class="text-muted">{{ $e->created_at->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
                                <h3>No students found</h3>
                                <p>Students appear here once they submit enrollment applications.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($enrollments) && $enrollments->hasPages())
        <div class="pagination-wrap">
            <span class="pagination-info">Showing {{ $enrollments->firstItem() }}–{{ $enrollments->lastItem() }} of {{ $enrollments->total() }}</span>
            <div class="pagination">{{ $enrollments->appends(request()->query())->links() }}</div>
        </div>
        @endif
    </div>

</div>
@endsection
