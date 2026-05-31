@extends('layouts.enrollment')
@section('title', 'Admin Dashboard — EnrollEase')
@section('body-class', 'role-admin')

@section('content')
<div class="page-wrapper">

    @if(session('success'))
    <div class="alert alert-success mb-6"><i class="fa-solid fa-circle-check"></i><div>{{ session('success') }}</div></div>
    @endif
    @if(session('error'))
    <div class="alert alert-error mb-6"><i class="fa-solid fa-triangle-exclamation"></i><div>{{ session('error') }}</div></div>
    @endif

    {{-- Hero --}}
    <div class="dash-hero">
        <div class="dash-hero__left">
            <p class="dash-hero__eyebrow"><i class="fa-solid fa-shield-halved"></i> Administrator · EnrollEase</p>
            <h1 class="dash-hero__title">Welcome back, {{ explode(' ', session('sso_name', 'Administrator'))[0] }}.</h1>
            <p class="dash-hero__sub">Monitor enrollment activity across all students and rooms.</p>
        </div>
        <div class="dash-hero__actions">
            <a href="{{ route('admin.enrollments.index') }}" class="dash-hero__btn">
                <i class="fa-solid fa-clipboard-list"></i> View Enrollments
            </a>
            <a href="{{ route('admin.rooms') }}" class="dash-hero__btn dash-hero__btn--outline">
                <i class="fa-solid fa-door-open"></i> View Rooms
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);">
        <div class="stat-card">
            <div class="stat-icon red"><i class="fa-solid fa-clipboard-list"></i></div>
            <div class="stat-info">
                <div class="stat-label">Total Enrollments</div>
                <div class="stat-value">{{ $totalEnrollments ?? 0 }}</div>
                <div class="stat-sub">All submissions</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fa-solid fa-clock"></i></div>
            <div class="stat-info">
                <div class="stat-label">Pending</div>
                <div class="stat-value">{{ $pendingCount ?? 0 }}</div>
                <div class="stat-sub">Awaiting officer review</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-info">
                <div class="stat-label">Enrolled</div>
                <div class="stat-value">{{ $enrolledCount ?? 0 }}</div>
                <div class="stat-sub">Assigned to rooms</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-magnifying-glass-check"></i></div>
            <div class="stat-info">
                <div class="stat-label">Approved</div>
                <div class="stat-value">{{ $approvedCount ?? 0 }}</div>
                <div class="stat-sub">Awaiting room assignment</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fa-solid fa-door-open"></i></div>
            <div class="stat-info">
                <div class="stat-label">Rooms</div>
                <div class="stat-value">{{ $roomCount ?? 0 }}</div>
                <div class="stat-sub">Active classrooms</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fa-solid fa-circle-xmark"></i></div>
            <div class="stat-info">
                <div class="stat-label">Rejected</div>
                <div class="stat-value">{{ $rejectedCount ?? 0 }}</div>
                <div class="stat-sub">Not approved</div>
            </div>
        </div>
    </div>

    {{-- Monitor links --}}
    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-eye"></i> Monitor</span>
        </div>
        <div class="card-body">
            <div class="action-grid">
                <a href="{{ route('admin.enrollments.index') }}" class="action-card">
                    <div class="action-card-icon"><i class="fa-solid fa-list-checks"></i></div>
                    <div class="action-card-title">All Enrollments</div>
                    <div class="action-card-desc">Browse all student enrollment applications and their current status.</div>
                </a>
                <a href="{{ route('admin.rooms') }}" class="action-card">
                    <div class="action-card-icon"><i class="fa-solid fa-door-open"></i></div>
                    <div class="action-card-title">Room Overview</div>
                    <div class="action-card-desc">View classroom assignments and capacity across all grade levels.</div>
                </a>
                <a href="{{ route('admin.students') }}" class="action-card">
                    <div class="action-card-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="action-card-title">Student Records</div>
                    <div class="action-card-desc">Browse all student records and their enrollment status.</div>
                </a>
                <a href="{{ route('admin.enrollments.index', ['status' => 'pending']) }}" class="action-card">
                    <div class="action-card-icon"><i class="fa-solid fa-clock"></i></div>
                    <div class="action-card-title">Pending Applications</div>
                    <div class="action-card-desc">View applications currently awaiting officer processing.</div>
                </a>
            </div>
        </div>
    </div>

    {{-- Recent enrollments (read-only) --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> Recent Enrollments</span>
            <a href="{{ route('admin.enrollments.index') }}" class="btn-ghost-white">View All</a>
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
                    @forelse($recentEnrollments ?? [] as $e)
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
                                <h3>No enrollments yet</h3>
                                <p>Student submissions will appear here.</p>
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
