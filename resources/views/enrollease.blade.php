<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>EnrollEase — Enrollment Management</title>
    <script>
        // Module configuration injected from Laravel
        window.ENROLLEASE_API_BASE = "{{ config('app.url') }}";
        window.PORTAL_ORIGIN       = "{{ config('app.portal_url') }}";
        window.SSO_TIMEOUT_MS      = 8000;
        window.DEORIS_SSO_MODE     = "module";
    </script>
</head>
<body>

<div id="enrollease-root" style="
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
">
    <div id="enrollease-loader" style="
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 16px;
    ">
        <div style="
            width: 48px; height: 48px;
            border-radius: 50%;
            border: 4px solid rgba(0,0,0,.1);
            border-top-color: #722F37;
            animation: spin .8s linear infinite;
        "></div>
        <p style="color:#722F37; font-weight:700; font-size:15px;">Loading…</p>
        <p id="enrollease-loader-error" style="
            color:#dc2626; font-size:13px;
            display:none; max-width:360px; text-align:center;
        "></p>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<!-- module-bridge.js MUST load BEFORE enrollease.js -->
<script src="{{ rtrim(config('app.portal_url', 'https://deoris.test'), '/') }}/module-bridge.js"></script>
<script src="{{ asset('js/enrollease.js') }}?v={{ filemtime(public_path('js/enrollease.js')) }}"></script>

</body>
</html>
