@extends('layouts.enrollment')
@section('title', 'Apply for Enrollment — EnrollEase')
@section('body-class', 'role-student')

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div>
            <h1><i class="fa-solid fa-graduation-cap"></i> Enrollment Application</h1>
            <p>Fill in your details to submit your enrollment application.</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('student.dashboard') }}" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-error mb-6">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>
            <strong>Please fix the following:</strong>
            <ul style="margin:.4rem 0 0 1rem;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    <form action="{{ route('student.enrollment.store') }}" method="POST" id="enrollForm" novalidate>
        @csrf

        {{-- Step 1: Student info (pre-filled from SSO) --}}
        <div class="card mb-6">
            <div class="card-header">
                <span class="card-title">
                    <span class="step-badge">1</span>
                    Student Information
                </span>
            </div>
            <div class="card-body">
                <div class="info-notice">
                    <i class="fa-solid fa-circle-info"></i>
                    <div>
                        <strong>Your name and email are pre-filled from your account.</strong>
                        <p>Please complete the remaining fields below.</p>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-control" value="{{ $student->name ?? '' }}" disabled>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" class="form-control" value="{{ $student->email ?? '' }}" disabled>
                    </div>
                    <div class="form-group {{ $errors->has('gender') ? 'has-error' : '' }}">
                        <label>Gender <span class="req">*</span></label>
                        <select name="gender" class="form-control" required>
                            <option value="">Select gender…</option>
                            <option value="male"   {{ old('gender') === 'male'   ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group {{ $errors->has('date_of_birth') ? 'has-error' : '' }}">
                        <label>Date of Birth <span class="req">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control"
                               value="{{ old('date_of_birth') }}"
                               max="{{ date('Y-m-d', strtotime('-5 years')) }}"
                               required>
                        @error('date_of_birth')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group {{ $errors->has('contact_number') ? 'has-error' : '' }}">
                        <label>Contact Number <span class="req">*</span></label>
                        <input type="text" name="contact_number" class="form-control"
                               value="{{ old('contact_number') }}"
                               placeholder="e.g. 09171234567"
                               maxlength="20" required>
                        @error('contact_number')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group {{ $errors->has('lrn') ? 'has-error' : '' }}">
                        <label>LRN <span class="label-optional">(optional)</span></label>
                        <input type="text" name="lrn" class="form-control"
                               value="{{ old('lrn') }}"
                               placeholder="12-digit Learner Reference Number"
                               maxlength="12">
                        @error('lrn')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group full {{ $errors->has('address') ? 'has-error' : '' }}">
                        <label>Home Address <span class="req">*</span></label>
                        <input type="text" name="address" class="form-control"
                               value="{{ old('address') }}"
                               placeholder="Street, Barangay, City/Municipality, Province"
                               maxlength="300" required>
                        @error('address')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: Academic details --}}
        <div class="card mb-6">
            <div class="card-header">
                <span class="card-title">
                    <span class="step-badge">2</span>
                    Academic Details
                </span>
            </div>
            <div class="card-body">
                <div class="form-grid-2">
                    <div class="form-group {{ $errors->has('grade_level') ? 'has-error' : '' }}">
                        <label>Grade Level <span class="req">*</span></label>
                        <select name="grade_level" class="form-control" required>
                            <option value="">Select grade level…</option>
                            @for($g = 1; $g <= 12; $g++)
                                <option value="{{ $g }}" {{ old('grade_level') == $g ? 'selected' : '' }}>
                                    Grade {{ $g }}
                                </option>
                            @endfor
                        </select>
                        @error('grade_level')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>School Year</label>
                        <input type="text" class="form-control" value="{{ date('Y') . '–' . (date('Y')+1) }}" disabled>
                    </div>
                    <div class="form-group {{ $errors->has('previous_school') ? 'has-error' : '' }}">
                        <label>Previous School <span class="label-optional">(optional)</span></label>
                        <input type="text" name="previous_school" class="form-control"
                               value="{{ old('previous_school') }}"
                               placeholder="Name of last school attended"
                               maxlength="150">
                        @error('previous_school')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group {{ $errors->has('last_grade_completed') ? 'has-error' : '' }}">
                        <label>Last Grade Completed <span class="label-optional">(optional)</span></label>
                        <select name="last_grade_completed" class="form-control">
                            <option value="">Select…</option>
                            @for($g = 1; $g <= 12; $g++)
                                <option value="{{ $g }}" {{ old('last_grade_completed') == $g ? 'selected' : '' }}>
                                    Grade {{ $g }}
                                </option>
                            @endfor
                        </select>
                        @error('last_grade_completed')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 3: Guardian information --}}
        <div class="card mb-6">
            <div class="card-header">
                <span class="card-title">
                    <span class="step-badge">3</span>
                    Guardian Information
                </span>
            </div>
            <div class="card-body">
                <div class="form-grid-2">
                    <div class="form-group {{ $errors->has('guardian_name') ? 'has-error' : '' }}">
                        <label>Guardian Name <span class="req">*</span></label>
                        <input type="text" name="guardian_name" class="form-control"
                               value="{{ old('guardian_name') }}"
                               placeholder="Full name of parent or guardian"
                               maxlength="150" required>
                        @error('guardian_name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group {{ $errors->has('guardian_relationship') ? 'has-error' : '' }}">
                        <label>Relationship <span class="req">*</span></label>
                        <select name="guardian_relationship" class="form-control" required>
                            <option value="">Select…</option>
                            @foreach(['Father','Mother','Guardian','Grandparent','Sibling','Relative','Other'] as $rel)
                                <option value="{{ strtolower($rel) }}" {{ old('guardian_relationship') === strtolower($rel) ? 'selected' : '' }}>
                                    {{ $rel }}
                                </option>
                            @endforeach
                        </select>
                        @error('guardian_relationship')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group {{ $errors->has('guardian_contact') ? 'has-error' : '' }}">
                        <label>Guardian Contact Number <span class="req">*</span></label>
                        <input type="text" name="guardian_contact" class="form-control"
                               value="{{ old('guardian_contact') }}"
                               placeholder="e.g. 09171234567"
                               maxlength="20" required>
                        @error('guardian_contact')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group {{ $errors->has('guardian_occupation') ? 'has-error' : '' }}">
                        <label>Guardian Occupation <span class="label-optional">(optional)</span></label>
                        <input type="text" name="guardian_occupation" class="form-control"
                               value="{{ old('guardian_occupation') }}"
                               placeholder="e.g. Teacher, Farmer, OFW"
                               maxlength="100">
                        @error('guardian_occupation')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="card">
            <div class="card-body">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex gap-2 items-center text-sm" style="color:var(--text-muted);">
                        <i class="fa-solid fa-circle-info"></i>
                        Once submitted, your application will be reviewed by the Admission Officer.
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('student.dashboard') }}" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fa-solid fa-paper-plane"></i> Submit Application
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>

@push('scripts')
<script>
document.getElementById('enrollForm').addEventListener('submit', function () {
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting…';
});
</script>
@endpush
@endsection
