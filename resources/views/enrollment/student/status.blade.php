@extends('layouts.enrollment')
@section('title', 'My Enrollment — EnrollEase')
@section('body-class', 'role-student')

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div>
            <h1><i class="fa-solid fa-list-checks"></i> My Enrollment</h1>
            <p>Track the status and details of your enrollment application.</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('student.dashboard') }}" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

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

    @if($enrollment ?? null)

    {{-- Status banner --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="status-banner">
                <div class="status-banner-item">
                    <span class="status-banner-label">Reference Number</span>
                    <span class="status-banner-value">#{{ str_pad($enrollment->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="status-banner-item">
                    <span class="status-banner-label">Submitted</span>
                    <span style="font-weight:600;">{{ $enrollment->created_at->format('F d, Y') }}</span>
                </div>
                <div class="status-banner-item">
                    <span class="status-banner-label">Status</span>
                    <span class="badge badge-{{ $enrollment->status }}" style="font-size:.85rem;padding:6px 14px;">
                        {{ $enrollment->status_label }}
                    </span>
                </div>
            </div>

            @if($enrollment->remarks)
            <div class="alert alert-info mt-4">
                <i class="fa-solid fa-clipboard"></i>
                <div><strong>Officer Note:</strong> {{ $enrollment->remarks }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Progress tracker --}}
    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-chart-line"></i> Application Progress</span>
        </div>
        <div class="card-body">
            @php
                $stageMap = ['pending' => 0, 'reviewing' => 1, 'approved' => 2, 'enrolled' => 3];
                $currentStage = $stageMap[$enrollment->status] ?? -1;
            @endphp

            @if(in_array($enrollment->status, ['rejected', 'cancelled']))
            <div class="alert {{ $enrollment->status === 'rejected' ? 'alert-error' : 'alert-warning' }}">
                <i class="fa-solid fa-circle-xmark"></i>
                <div>
                    @if($enrollment->status === 'rejected')
                        Your enrollment application has been <strong>rejected</strong>. Please contact the school for more information.
                    @else
                        You have cancelled this enrollment application.
                    @endif
                </div>
            </div>
            @else
            <div class="progress-steps">
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
            @endif
        </div>
    </div>

    {{-- Details --}}
    <div class="detail-two-col">
        <div class="card mb-0">
            <div class="card-header">
                <span class="card-title"><i class="fa-solid fa-user"></i> Personal Information</span>
            </div>
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Full Name</label>
                        <span>{{ $enrollment->student_name }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Email</label>
                        <span>{{ $enrollment->email }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-0">
            <div class="card-header">
                <span class="card-title"><i class="fa-solid fa-graduation-cap"></i> Academic Details</span>
            </div>
            <div class="card-body">
                <div class="detail-grid">
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
                    <div class="detail-item">
                        <label>Section</label>
                        <span>{{ $enrollment->room->section ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Actions row --}}
    @if(($enrollment->status ?? '') === 'enrolled')
    <div class="flex gap-2 mt-4 flex-wrap">
        <div class="alert alert-success" style="flex:1;">
            <i class="fa-solid fa-circle-check"></i>
            <div>
                <strong>Enrollment complete.</strong>
                You have been successfully enrolled.
                @if($enrollment->room ?? null)
                    Your room is <strong>{{ $enrollment->room->name }}</strong>
                    @if($enrollment->room->section) — Section {{ $enrollment->room->section }}@endif.
                @endif
            </div>
        </div>
    </div>
    @endif

    @else
    {{-- Empty state --}}
    <div class="card">
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-clipboard-list"></i></div>
            <h3>No enrollment application yet</h3>
            <p>No enrollment application on file.</p>
            <a href="{{ route('student.enrollment.create') }}" class="btn btn-primary mt-4">
                <i class="fa-solid fa-file-pen"></i> Apply Now
            </a>
        </div>
    </div>
    @endif

</div>
@endsection
