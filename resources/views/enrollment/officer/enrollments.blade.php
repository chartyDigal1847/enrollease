@extends('layouts.enrollment')
@section('title', 'Enrollments — Officer')
@section('body-class', 'role-officer')

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div>
            <h1><i class="fa-solid fa-clipboard-list"></i> Enrollment Applications</h1>
            <p>Review, verify, and update enrollment status.</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('officer.dashboard') }}" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="alert alert-success mb-6">
        <i class="fa-solid fa-circle-check"></i>
        <div>{{ session('success') }}</div>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-error mb-6">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>{{ session('error') }}</div>
    </div>
    @endif

    <form method="GET" action="{{ route('officer.enrollments.index') }}" class="filter-bar">
        <div class="form-group">
            <label>Search</label>
            <input type="text" name="search" class="form-control" placeholder="Name or email…" value="{{ request('search') }}">
        </div>
        <div class="form-group">
            <label>Grade Level</label>
            <select name="grade_level" class="form-control">
                <option value="">All Grades</option>
                @for($g = 1; $g <= 12; $g++)
                    <option value="{{ $g }}" {{ request('grade_level') == $g ? 'selected' : '' }}>Grade {{ $g }}</option>
                @endfor
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="">All Status</option>
                @foreach(['pending','verified','approved','rejected','enrolled','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-bar-actions">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-magnifying-glass"></i> Filter
            </button>
            <a href="{{ route('officer.enrollments.index') }}" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-table-list"></i> All Enrollments</span>
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
                        <th>Actions</th>
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
                        <td><span class="badge badge-{{ $e->status }}">{{ ucfirst($e->status) }}</span></td>
                        <td>
                            <div class="flex gap-2">
                                <a href="{{ route('officer.enrollments.show', $e->id) }}" class="btn btn-ghost btn-sm" title="View">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                @if($e->status === 'pending')
                                    <form method="POST" action="{{ route('officer.enrollments.verify', $e->id) }}">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-warning btn-sm">
                                            <i class="fa-solid fa-magnifying-glass"></i> Verify
                                        </button>
                                    </form>
                                @endif
                                <button class="btn btn-ghost btn-sm" onclick="openModal('statusModal{{ $e->id }}')" title="Update Status">
                                    <i class="fa-solid fa-sliders"></i>
                                </button>
                            </div>

                            <div class="modal-overlay" id="statusModal{{ $e->id }}">
                                <div class="modal">
                                    <div class="modal-header">
                                        <span class="modal-title">Update Status — #{{ str_pad($e->id, 4, '0', STR_PAD_LEFT) }}</span>
                                        <button class="modal-close" onclick="closeModal('statusModal{{ $e->id }}')"><i class="fa-solid fa-xmark"></i></button>
                                    </div>
                                    <form method="POST" action="{{ route('officer.enrollments.updateStatus', $e->id) }}">
                                        @csrf @method('PATCH')
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>New Status</label>
                                                <select name="status" class="form-control" required>
                                                    @foreach(['pending','verified','approved','rejected','cancelled'] as $s)
                                                        <option value="{{ $s }}" {{ $e->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Remarks <span class="label-optional">(optional)</span></label>
                                                <textarea name="remarks" class="form-control" rows="3"
                                                          placeholder="Add notes about this status change…">{{ $e->remarks }}</textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-ghost" onclick="closeModal('statusModal{{ $e->id }}')">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Update Status</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
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
