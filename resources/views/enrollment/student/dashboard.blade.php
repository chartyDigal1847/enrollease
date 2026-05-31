@extends('layouts.enrollment')
@section('title', 'Dashboard — EnrollEase')
@section('body-class', 'role-student')

@section('content')
<div class="page-wrapper">

    {{-- Hero banner --}}
    <div class="student-hero">
        <div class="student-hero__left">
            <p class="student-hero__eyebrow">
                <i class="fa-solid fa-graduation-cap"></i>
                Student Portal
            </p>
            <h1 class="student-hero__title">
                Welcome back, {{ explode(' ', session('sso_name', 'Student'))[0] }}.
            </h1>
            <p class="student-hero__sub">
                @if($enrollment ?? null)
                    Your enrollment application is currently
                    <strong class="student-hero__status student-hero__status--{{ $enrollment->status }}">
                        {{ $enrollment->status_label }}
                    </strong>.
                @else
                    You haven't submitted an enrollment application yet.
                @endif
            </p>
        </div>
        <div class="student-hero__badge">
            @if($enrollment ?? null)
                <span class="student-hero__badge-label">Reference No.</span>
                <strong class="student-hero__badge-ref">#{{ str_pad($enrollment->id, 4, '0', STR_PAD_LEFT) }}</strong>
                <span class="student-hero__badge-sub">{{ $enrollment->school_year ?? date('Y').'–'.(date('Y')+1) }}</span>
            @else
                <span class="student-hero__badge-label">School Year</span>
                <strong class="student-hero__badge-ref">{{ date('Y') }}–{{ date('Y')+1 }}</strong>
                <span class="student-hero__badge-sub">Enrollment open</span>
            @endif
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
    @if(session('info'))
    <div class="alert alert-info mb-6">
        <i class="fa-solid fa-circle-info"></i>
        <div>{{ session('info') }}</div>
    </div>
    @endif

    @if($enrollment ?? null)
    {{-- Enrollment summary card --}}
    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">
                <i class="fa-solid fa-clipboard-list"></i>
                Your Enrollment
            </span>
            <span class="badge badge-{{ $enrollment->status }}">{{ $enrollment->status_label }}</span>
        </div>
        <div class="card-body">

            {{-- Progress bar --}}
            @php
                $stageMap = ['pending' => 0, 'reviewing' => 1, 'approved' => 2, 'enrolled' => 3];
                $currentStage = $stageMap[$enrollment->status] ?? -1;
                $isTerminal = in_array($enrollment->status, ['rejected', 'cancelled']);
            @endphp

            @if(!$isTerminal)
            <div class="progress-steps mb-6">
                @php
                    $stages = [
                        ['label' => 'Submitted',    'stage' => 0],
                        ['label' => 'Under Review', 'stage' => 1],
                        ['label' => 'Approved',     'stage' => 2],
                        ['label' => 'Enrolled',     'stage' => 3],
                    ];
                @endphp
                @foreach($stages as $s)
                    @if(!$loop->first)
                        <div class="step-connector {{ $currentStage >= $s['stage'] ? 'done' : '' }}"></div>
                    @endif
                    <div class="step {{ $currentStage === $s['stage'] ? 'active' : ($currentStage > $s['stage'] ? 'completed' : '') }}">
                        <div class="step-dot">
                            @if($currentStage > $s['stage'])
                                <i class="fa-solid fa-check" style="font-size:.65rem;"></i>
                            @else
                                {{ $loop->iteration }}
                            @endif
                        </div>
                        <span>{{ $s['label'] }}</span>
                    </div>
                @endforeach
            </div>
            @else
            <div class="alert {{ $enrollment->status === 'rejected' ? 'alert-error' : 'alert-warning' }} mb-6">
                <i class="fa-solid fa-circle-xmark"></i>
                <div>
                    @if($enrollment->status === 'rejected')
                        Your enrollment application has been <strong>rejected</strong>. Please contact the school for more information.
                    @else
                        You have cancelled this enrollment application.
                    @endif
                </div>
            </div>
            @endif

            <div class="detail-grid">
                <div class="detail-item">
                    <label>Reference No.</label>
                    <span>#{{ str_pad($enrollment->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="detail-item">
                    <label>Grade Level</label>
                    <span>Grade {{ $enrollment->grade_level }}</span>
                </div>
                <div class="detail-item">
                    <label>School Year</label>
                    <span>{{ $enrollment->school_year ?? '—' }}</span>
                </div>
                <div class="detail-item">
                    <label>Room Assigned</label>
                    <span>{{ $enrollment->room->name ?? 'Not yet assigned' }}</span>
                </div>
            </div>

            @if($enrollment->remarks)
            <div class="alert alert-info mt-4">
                <i class="fa-solid fa-circle-info"></i>
                <div><strong>Note from officer:</strong> {{ $enrollment->remarks }}</div>
            </div>
            @endif

            <div class="flex gap-2 mt-4 flex-wrap">
                <a href="{{ route('student.enrollment.status') }}" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-file-lines"></i> View Full Details
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- Quick actions --}}
    <p class="section-label">Quick Actions</p>
    <div class="action-grid">
        @if(!($enrollment ?? null) || in_array($enrollment->status ?? '', ['cancelled', 'rejected']))
        <a href="{{ route('student.enrollment.create') }}" class="action-card action-card--primary">
            <div class="action-card-icon action-card-icon--primary">
                <i class="fa-solid fa-file-pen"></i>
            </div>
            <div class="action-card-title">Apply for Enrollment</div>
            <div class="action-card-desc">Submit your enrollment application for the upcoming school year.</div>
        </a>
        @endif

        <a href="{{ route('student.enrollment.status') }}" class="action-card">
            <div class="action-card-icon">
                <i class="fa-solid fa-list-checks"></i>
            </div>
            <div class="action-card-title">My Enrollment</div>
            <div class="action-card-desc">Track the status and details of your enrollment application.</div>
        </a>

        @if(($enrollment->status ?? '') === 'enrolled')
        <div class="action-card action-card--success">
            <div class="action-card-icon action-card-icon--success">
                <i class="fa-solid fa-door-open"></i>
            </div>
            <div class="action-card-title">Room Assigned</div>
            <div class="action-card-desc">
                {{ $enrollment->room->name ?? 'Room assignment pending.' }}
                @if($enrollment->room->section ?? null)
                    — Section {{ $enrollment->room->section }}
                @endif
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
