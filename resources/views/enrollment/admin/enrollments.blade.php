@extends('layouts.enrollment')
@section('title', 'All Enrollments — Admin')
@section('body-class', 'role-admin')

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div>
            <h1><i class="fa-solid fa-clipboard-list"></i> All Enrollments</h1>
            <p>Read-only view of all student enrollment applications.</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.enrollments.index') }}" class="filter-bar">
        <div class="form-group">
            <label>Search</label>
            <input type="text" name="search" class="form-control" placeholder="Name or email…" value="{{ request('search') }}">
        </div>
        <div class="form-group">
            <label>Grade Level</label>
            <select name="grade_level" class="form-control">
                <option value="">All Grades</option>
                @for($g = 6; $g <= 12; $g++)
                    <option value="{{ $g }}" {{ request('grade_level') == $g ? 'selected' : '' }}>Grade {{ $g }}</option>
                @endfor
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="">All Status</option>
                @foreach(['pending','reviewing','approved','rejected','enrolled','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                        {{ $s === 'reviewing' ? 'Under Review' : ucfirst($s) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="filter-bar-actions">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
            <a href="{{ route('admin.enrollments.index') }}" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-table-list"></i> Enrollment List</span>
            @if(isset($enrollments))
                <span class="record-count">{{ $enrollments->total() }} records</span>
            @endif
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Grade</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Room</th>
                        <th></th>
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
                        <td class="text-muted">{{ $e->created_at->format('M d, Y') }}</td>
                        <td><span class="badge badge-{{ $e->status }}">{{ $e->status_label }}</span></td>
                        <td class="text-muted">{{ $e->room->name ?? '—' }}</td>
                        <td>
                            <a href="{{ route('admin.enrollments.show', $e->id) }}" class="btn btn-ghost btn-sm" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                                <h3>No enrollments found</h3>
                                <p>Try adjusting your filters.</p>
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
