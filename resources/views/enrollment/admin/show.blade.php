@extends('layouts.enrollment')
@section('title', 'Enrollment #' . str_pad($enrollment->id, 4, '0', STR_PAD_LEFT) . ' — Admin View')
@section('body-class', 'role-admin')

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div>
            <h1><i class="fa-solid fa-file-lines"></i> Enrollment #{{ str_pad($enrollment->id, 4, '0', STR_PAD_LEFT) }}</h1>
            <p>Submitted {{ $enrollment->created_at->format('F d, Y \a\t h:i A') }} — read-only view</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('admin.enrollments.index') }}" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    {{-- Status bar --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <span class="badge badge-{{ $enrollment->status }}" style="font-size:.85rem;padding:6px 14px;">
                        {{ $enrollment->status_label }}
                    </span>
                    <span class="text-muted text-sm">Current enrollment status</span>
                </div>
                @if($enrollment->room)
                <div class="flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-door-open" style="color:var(--primary);"></i>
                    <strong>{{ $enrollment->room->name }}</strong>
                    @if($enrollment->room->section)
                        <span class="text-muted">— Section {{ $enrollment->room->section }}</span>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="show-layout">

        {{-- Left column --}}
        <div>
            <div class="card mb-6">
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
                            <label>Email Address</label>
                            <span>{{ $enrollment->email }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Date of Birth</label>
                            <span>{{ $enrollment->date_of_birth ? $enrollment->date_of_birth->format('F d, Y') : '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Gender</label>
                            <span>{{ ucfirst($enrollment->gender ?? '—') }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Contact Number</label>
                            <span>{{ $enrollment->contact_number ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Nationality</label>
                            <span>{{ $enrollment->nationality ?? 'Filipino' }}</span>
                        </div>
                        <div class="detail-item full">
                            <label>Address</label>
                            <span>{{ $enrollment->address ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-6">
                <div class="card-header">
                    <span class="card-title"><i class="fa-solid fa-graduation-cap"></i> Academic Information</span>
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
                            <label>Previous School</label>
                            <span>{{ $enrollment->previous_school ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Last Grade Completed</label>
                            <span>{{ $enrollment->last_grade_completed ? 'Grade '.$enrollment->last_grade_completed : '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <label>LRN</label>
                            <span>{{ $enrollment->lrn ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Average Grade</label>
                            <span>{{ $enrollment->average_grade ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fa-solid fa-people-roof"></i> Guardian Information</span>
                </div>
                <div class="card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Guardian Name</label>
                            <span>{{ $enrollment->guardian_name ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Relationship</label>
                            <span>{{ ucfirst($enrollment->guardian_relationship ?? '—') }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Contact Number</label>
                            <span>{{ $enrollment->guardian_contact ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Occupation</label>
                            <span>{{ $enrollment->guardian_occupation ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right column --}}
        <div>
            <div class="card mb-6">
                <div class="card-header">
                    <span class="card-title"><i class="fa-solid fa-folder-open"></i> Documents</span>
                </div>
                <div class="card-body">
                    <div class="info-notice" style="margin-bottom:1rem;">
                        <i class="fa-solid fa-circle-info"></i>
                        <div>Admission documents are stored in EntryEase to avoid duplicate uploads. EnrollEase only stores the report card.</div>
                    </div>
                    @php
                        $docs = [
                            ['label' => 'PSA Birth Certificate', 'key' => 'psa',    'path' => $enrollment->psa_path,         'source' => 'entryease'],
                            ['label' => '2×2 Photo',             'key' => 'photo',  'path' => $enrollment->photo_path,       'source' => 'entryease'],
                            ['label' => 'Report Card',           'key' => 'report', 'path' => $enrollment->report_card_path, 'source' => 'enrollease'],
                        ];
                    @endphp
                    <div class="doc-list">
                        @foreach($docs as $doc)
                        <div class="doc-item">
                            <span class="doc-item-label">{{ $doc['label'] }}</span>
                            @if($doc['path'])
                                <a href="{{ route('admin.documents.view', [$enrollment->id, $doc['key']]) }}"
                                   target="_blank" class="badge badge-approved" style="text-decoration:none;">
                                    <i class="fa-solid fa-eye"></i> View
                                </a>
                            @elseif($doc['source'] === 'entryease')
                                <a href="{{ route('admin.documents.view', [$enrollment->id, $doc['key']]) }}"
                                   target="_blank" class="badge badge-approved" style="text-decoration:none;">
                                    <i class="fa-solid fa-eye"></i> View from EntryEase
                                </a>
                            @else
                                <span class="badge badge-pending">Missing</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card mb-6">
                <div class="card-header">
                    <span class="card-title"><i class="fa-solid fa-door-open"></i> Room Assignment</span>
                </div>
                <div class="card-body">
                    @if($enrollment->room)
                        <div class="detail-grid" style="grid-template-columns:1fr;">
                            <div class="detail-item">
                                <label>Room</label>
                                <span>{{ $enrollment->room->name }}</span>
                            </div>
                            <div class="detail-item">
                                <label>Section</label>
                                <span>{{ $enrollment->room->section ?? '—' }}</span>
                            </div>
                            <div class="detail-item">
                                <label>Adviser</label>
                                <span>{{ $enrollment->room->adviser ?? '—' }}</span>
                            </div>
                        </div>
                    @else
                        <div class="empty-state" style="padding:20px 0;">
                            <div class="empty-icon"><i class="fa-solid fa-door-open"></i></div>
                            <p>No room assigned yet.</p>
                        </div>
                    @endif
                </div>
            </div>

            @if($enrollment->remarks)
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fa-solid fa-comment-lines"></i> Officer Remarks</span>
                </div>
                <div class="card-body">
                    <p class="text-sm" style="color:var(--text-muted);line-height:1.6;">{{ $enrollment->remarks }}</p>
                </div>
            </div>
            @endif
        </div>

    </div>

</div>
@endsection
