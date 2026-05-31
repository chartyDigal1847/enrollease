@extends('layouts.enrollment')
@section('title', 'Officer Dashboard — EnrollEase')
@section('body-class', 'role-officer')

@section('content')
<div class="page-wrapper">

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

    {{-- Hero --}}
    <div class="dash-hero">
        <div class="dash-hero__left">
            <p class="dash-hero__eyebrow"><i class="fa-solid fa-clipboard-check"></i> Admission Officer · EnrollEase</p>
            <h1 class="dash-hero__title">Welcome, {{ explode(' ', session('sso_name', 'Officer'))[0] }}.</h1>
            <p class="dash-hero__sub">Review and process student enrollment applications.</p>
        </div>
        <div class="dash-hero__actions">
            <a href="{{ route('officer.enrollments.index', ['status' => 'pending']) }}" class="dash-hero__btn">
                <i class="fa-solid fa-clock"></i>
                Pending Queue
                @if(($pendingCount ?? 0) > 0)
                    <span class="dash-hero__count">{{ $pendingCount }}</span>
                @endif
            </a>
            <a href="{{ route('officer.enrollments.index') }}" class="dash-hero__btn dash-hero__btn--outline">
                <i class="fa-solid fa-clipboard-list"></i> All Enrollments
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fa-solid fa-clock"></i></div>
            <div class="stat-info">
                <div class="stat-label">Pending Review</div>
                <div class="stat-value">{{ $pendingCount ?? 0 }}</div>
                <div class="stat-sub">Needs your attention</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-magnifying-glass"></i></div>
            <div class="stat-info">
                <div class="stat-label">Under Review</div>
                <div class="stat-value">{{ $reviewingCount ?? $verifiedCount ?? 0 }}</div>
                <div class="stat-sub">Awaiting approval</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-info">
                <div class="stat-label">Approved</div>
                <div class="stat-value">{{ $approvedCount ?? 0 }}</div>
                <div class="stat-sub">Ready for room assignment</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fa-solid fa-door-open"></i></div>
            <div class="stat-info">
                <div class="stat-label">Enrolled</div>
                <div class="stat-value">{{ $enrolledCount ?? 0 }}</div>
                <div class="stat-sub">Assigned to rooms</div>
            </div>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-bolt"></i> Quick Actions</span>
        </div>
        <div class="card-body">
            <div class="action-grid">
                <a href="{{ route('officer.enrollments.index') }}" class="action-card">
                    <div class="action-card-icon"><i class="fa-solid fa-list-checks"></i></div>
                    <div class="action-card-title">All Enrollments</div>
                    <div class="action-card-desc">View and process all student enrollment submissions.</div>
                </a>
                <a href="{{ route('officer.enrollments.index', ['status' => 'pending']) }}" class="action-card">
                    <div class="action-card-icon"><i class="fa-solid fa-clock"></i></div>
                    <div class="action-card-title">Pending Queue</div>
                    <div class="action-card-desc">Process enrollments waiting for verification.</div>
                </a>
                <a href="{{ route('officer.enrollments.index', ['status' => 'approved']) }}" class="action-card">
                    <div class="action-card-icon"><i class="fa-solid fa-magnifying-glass-check"></i></div>
                    <div class="action-card-title">Approved</div>
                    <div class="action-card-desc">Approved students ready for room assignment.</div>
                </a>
                <a href="{{ route('officer.rooms.index') }}" class="action-card">
                    <div class="action-card-icon"><i class="fa-solid fa-door-open"></i></div>
                    <div class="action-card-title">Manage Rooms</div>
                    <div class="action-card-desc">Create rooms and assign approved students to classrooms.</div>
                </a>
            </div>
        </div>
    </div>

    {{-- Pending enrollments table --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> Pending Enrollments</span>
            <a href="{{ route('officer.enrollments.index') }}" class="btn-ghost-white">View All</a>
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
                    @forelse($pendingEnrollments ?? [] as $e)
                    <tr>
                        <td class="text-muted fw-600">#{{ str_pad($e->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td>
                            <strong>{{ $e->student_name }}</strong>
                            <span class="td-sub">{{ $e->email }}</span>
                        </td>
                        <td>Grade {{ $e->grade_level }}</td>
                        <td class="text-muted">{{ $e->created_at->format('M d, Y') }}</td>
                        <td><span class="badge badge-{{ $e->status }}">{{ $e->status_label }}</span></td>
                        <td>
                            <div class="flex gap-2">
                                <a href="{{ route('officer.enrollments.show', $e->id) }}" class="btn btn-ghost btn-sm">
                                    <i class="fa-solid fa-eye"></i> View
                                </a>
                                @if($e->status === 'pending')
                                    <form method="POST" action="{{ route('officer.enrollments.verify', $e->id) }}">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-warning btn-sm">
                                            <i class="fa-solid fa-magnifying-glass"></i> Verify
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-circle-check"></i></div>
                                <h3>All caught up!</h3>
                                <p>No pending enrollments at the moment.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
