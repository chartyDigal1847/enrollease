<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EnrollEase')</title>
    <script>
        if (window.self !== window.top) {
            document.documentElement.classList.add('is-framed');
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/enrollment.css') }}?v={{ filemtime(public_path('css/enrollment.css')) }}">
    @stack('styles')
</head>
@php
    $isEmbeddedRequest = request()->boolean('embedded')
        || request()->headers->get('Sec-Fetch-Dest') === 'iframe';
    $isEmbedded = (bool) session('sso_embedded', false) || $isEmbeddedRequest;

    $isAdminRoute   = request()->routeIs('admin.*');
    $isOfficerRoute = request()->routeIs('officer.*');
    $isStudentRoute = request()->routeIs('student.*');

    $role      = session('sso_role', 'student');
    $userName  = session('sso_name', 'User');
    $initial   = strtoupper(substr($userName, 0, 1)) ?: 'U';

    $roleLabel = match($role) {
        'admin'   => 'Admin',
        'officer' => 'Admission Officer',
        default   => 'Student',
    };
@endphp
<body class="@yield('body-class') {{ $isEmbedded ? 'is-embedded' : '' }}">
<div class="app-shell">

    {{-- ── Navbar — hidden when embedded ──────────────────────────── --}}
    @if(!$isEmbedded)
    <header class="app-header" role="banner">
        <div class="app-header-inner">

            {{-- Brand --}}
            <a href="{{ $isAdminRoute ? route('admin.dashboard') : ($isOfficerRoute ? route('officer.dashboard') : route('student.dashboard')) }}"
               class="app-brand" aria-label="EnrollEase home">
                <div class="app-brand-badge" aria-hidden="true">E</div>
                <div>
                    <div class="app-brand-name">EnrollEase</div>
                    <small class="app-brand-sub">Enrollment Management</small>
                </div>
            </a>

            {{-- Nav links --}}
            <nav class="app-nav" aria-label="Main navigation">
                @if($isAdminRoute)
                    <a href="{{ route('admin.dashboard') }}"
                       class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-squares-four"></i><span>Dashboard</span>
                    </a>
                    <a href="{{ route('admin.enrollments.index') }}"
                       class="{{ request()->routeIs('admin.enrollments*') ? 'active' : '' }}">
                        <i class="fa-solid fa-clipboard-list"></i><span>Enrollments</span>
                    </a>
                    <a href="{{ route('admin.rooms') }}"
                       class="{{ request()->routeIs('admin.rooms') ? 'active' : '' }}">
                        <i class="fa-solid fa-door-open"></i><span>Rooms</span>
                    </a>
                    <a href="{{ route('admin.students') }}"
                       class="{{ request()->routeIs('admin.students') ? 'active' : '' }}">
                        <i class="fa-solid fa-users"></i><span>Students</span>
                    </a>
                @elseif($isOfficerRoute)
                    <a href="{{ route('officer.dashboard') }}"
                       class="{{ request()->routeIs('officer.dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-squares-four"></i><span>Dashboard</span>
                    </a>
                    <a href="{{ route('officer.enrollments.index') }}"
                       class="{{ request()->routeIs('officer.enrollments*') ? 'active' : '' }}">
                        <i class="fa-solid fa-clipboard-list"></i><span>Enrollments</span>
                    </a>
                    <a href="{{ route('officer.rooms.index') }}"
                       class="{{ request()->routeIs('officer.rooms*') ? 'active' : '' }}">
                        <i class="fa-solid fa-door-open"></i><span>Rooms</span>
                    </a>
                @else
                    <a href="{{ route('student.dashboard') }}"
                       class="{{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-squares-four"></i><span>Dashboard</span>
                    </a>
                    @php
                        $hasActiveEnrollment = session('sso_email')
                            ? \App\Models\Enrollment::where('email', session('sso_email'))
                                ->whereNotIn('status', ['cancelled', 'rejected'])
                                ->exists()
                            : false;
                    @endphp
                    @if(!$hasActiveEnrollment)
                    <a href="{{ route('student.enrollment.create') }}"
                       class="{{ request()->routeIs('student.enrollment.create') ? 'active' : '' }}">
                        <i class="fa-solid fa-file-pen"></i><span>Apply</span>
                    </a>
                    @endif
                    <a href="{{ route('student.enrollment.status') }}"
                       class="{{ request()->routeIs('student.enrollment.status') ? 'active' : '' }}">
                        <i class="fa-solid fa-list-checks"></i><span>My Enrollment</span>
                    </a>
                @endif
            </nav>

            {{-- Search bar --}}
            <div class="app-search" id="appSearch">
                <button type="button" class="app-search-toggle" id="searchToggleBtn" aria-label="Search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <div class="app-search-panel" id="searchPanel" hidden>
                    <div class="app-search-inner">
                        <i class="fa-solid fa-magnifying-glass app-search-icon"></i>
                        <input type="text"
                               id="searchInput"
                               class="app-search-input"
                               placeholder="Search enrollments, students…"
                               autocomplete="off"
                               aria-label="Search">
                        <button type="button" class="app-search-clear" id="searchClearBtn" hidden aria-label="Clear search">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <ul class="app-search-results" id="searchResults" hidden></ul>
                    <p class="app-search-empty" id="searchEmpty" hidden>No results found.</p>
                </div>
            </div>

            {{-- Notification bell --}}
            @include('enrollment.partials.notification-bell')

            {{-- User chip --}}
            <div class="app-user-chip" aria-label="Signed in as {{ $userName }}">
                <div class="app-user-avatar" aria-hidden="true">{{ $initial }}</div>
                <span>{{ $userName }}</span>
                <span class="app-role-badge">{{ $roleLabel }}</span>
            </div>

        </div>
    </header>
    @endif

    {{-- ── Main ─────────────────────────────────────────────────── --}}
    <main class="app-main app-main--embedded" id="main-content">
        <div class="app-content">
            <div class="role-page-layer">
                <div class="role-main-box">
                    @yield('content')
                </div>
            </div>
        </div>
    </main>

</div>{{-- .app-shell --}}

{{-- Initial notifications from session flash — standalone mode only --}}
@if(!$isEmbedded)
@php
    $initialNotifications = [];
    if (session('success')) $initialNotifications[] = ['title' => 'Success', 'message' => session('success'), 'type' => 'success'];
    if (session('error'))   $initialNotifications[] = ['title' => 'Error',   'message' => session('error'),   'type' => 'error'];
    if (session('info'))    $initialNotifications[] = ['title' => 'Notice',  'message' => session('info'),    'type' => 'info'];
@endphp
<script>
(function () {
    var fromSession = @json($initialNotifications);
    window.ENROLLEASE_INITIAL_NOTIFICATIONS = (window.ENROLLEASE_INITIAL_NOTIFICATIONS || []).concat(fromSession);
})();
</script>
@endif

{{-- Notifications JS — only in standalone mode (portal handles it when embedded) --}}
@if(!$isEmbedded)
<script src="{{ asset('js/notifications.js') }}?v={{ filemtime(public_path('js/notifications.js')) }}" defer></script>
@endif
<script src="{{ asset('js/enrollment.js') }}?v={{ filemtime(public_path('js/enrollment.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
