<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * EnrollEaseController
 * Exact copy of EntryEase's EntryEaseController pattern — adapted for EnrollEase.
 */
class EnrollEaseController extends Controller
{
    private function debugLog(string $hypothesisId, string $location, string $message, array $data = []): void
    {
        try {
            $payload = json_encode([
                'sessionId' => '0cc008',
                'runId' => 'run5',
                'hypothesisId' => $hypothesisId,
                'location' => $location,
                'message' => $message,
                'data' => $data,
                'timestamp' => (int) floor(microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES);

            if ($payload === false) {
                return;
            }

            file_put_contents('C:/xampp/htdocs/deoris/debug-0cc008.log', $payload . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // Debug logging failures must not break SSO.
        }
    }

    public function ssoExchange(Request $request)
    {
        $validated = $request->validate([
            'token'    => 'required|string|max:500',
            'embedded' => 'sometimes|boolean',
        ]);
        // #region agent log
        $this->debugLog('H9', 'EnrollEaseController::ssoExchange:entry', 'enrollease ssoExchange called', [
            'hasToken' => !empty($validated['token']),
            'embedded' => (bool) ($validated['embedded'] ?? false),
            'sessionId' => $request->session()->getId(),
        ]);
        // #endregion

        $portalUrl = rtrim((string) config('app.portal_url', 'https://deoris.test'), '/');
        $exchangeUrl = $portalUrl . '/api/v1/sso/exchange';
        $maxAttempts = 3;
        $attempt = 0;
        $response = null;

        // #region agent log
        $this->debugLog('H43', 'EnrollEaseController::ssoExchange:exchangeStart', 'enrollease portal exchange started', [
            'exchangeUrl' => $exchangeUrl,
            'maxAttempts' => $maxAttempts,
        ]);
        // #endregion

        do {
            $attempt++;
            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $validated['token'],
            ])->post($exchangeUrl, [
                'token' => $validated['token'],
            ]);

            $status = $response->status();
            $retryable = $status === 429 || $status >= 500;
            // #region agent log
            $this->debugLog('H43', 'EnrollEaseController::ssoExchange:portalResponse', 'enrollease portal exchange response', [
                'attempt' => $attempt,
                'status' => $status,
                'ok' => $response->ok(),
                'retryable' => $retryable,
            ]);
            // #endregion

            if ($response->ok() || ! $retryable) {
                break;
            }
        } while ($attempt < $maxAttempts);

        if (! $response || ! $response->ok()) {
            $status = $response?->status() ?? 500;
            $bodySnippet = substr((string) ($response?->body() ?? ''), 0, 160);

            // #region agent log
            $this->debugLog('H43', 'EnrollEaseController::ssoExchange:exchangeFailed', 'enrollease exchange failed after retries', [
                'status' => $status,
                'attempts' => $attempt,
                'bodySnippet' => $bodySnippet,
            ]);
            // #endregion

            if ($status === 401 || $status === 422) {
                return response()->json(['success' => false, 'message' => 'Invalid SSO token'], 401);
            }

            if ($status === 429 || $status >= 500) {
                return response()->json(['success' => false, 'message' => 'Portal SSO temporarily unavailable'], 503);
            }

            return response()->json(['success' => false, 'message' => 'Portal SSO exchange failed'], 502);
        }

        $payload = $response->json();
        $user = $payload['user'] ?? $payload['data']['user'] ?? null;
        if (!is_array($user) || empty($user['id'])) {
            // #region agent log
            $this->debugLog('H9', 'EnrollEaseController::ssoExchange:invalidPayload', 'enrollease payload missing user id', [
                'hasUserArray' => is_array($user),
            ]);
            // #endregion
            return response()->json(['success' => false, 'message' => 'Invalid SSO response'], 401);
        }

        $newRole = $this->normalizeRole((string) ($user['role'] ?? 'student'));
        $name = (string) ($user['name'] ?? '');
        $email = (string) ($user['email'] ?? '');
        $id = (string) $user['id'];
        $embedded = (bool) ($validated['embedded'] ?? false);

        $request->session()->flush();
        $request->session()->put([
            'sso_id'               => $id,
            'sso_role'             => $newRole,
            'sso_name'             => $name,
            'sso_email'            => $email,
            'sso_embedded'         => $embedded,
            'student_email'        => $email,
            'user'                 => compact('id', 'name', 'email') + ['role' => $newRole],
            'sso_authenticated_at' => now()->timestamp,
        ]);
        // #region agent log
        $this->debugLog('H9', 'EnrollEaseController::ssoExchange:sessionHydrated', 'enrollease session hydrated after exchange', [
            'ssoId' => $id,
            'role' => $newRole,
            'sessionId' => $request->session()->getId(),
        ]);
        // #endregion

        return response()->json([
            'success'  => true,
            'redirect' => $this->dashboardUrl($newRole),
            'role'     => $newRole,
            'user'     => ['id' => $id, 'role' => $newRole, 'name' => $name, 'email' => $email],
        ]);
    }

    /**
     * GET /
     * Serve the SSO shell. Only flush stale fields when not already authenticated.
     * DO NOT regenerate CSRF token — breaks form submission.
     */
    public function index(Request $request)
    {
        if (! $this->hasValidSsoSession($request)) {
            $request->session()->forget([
                'sso_role', 'sso_name', 'sso_email', 'sso_id',
                'user', 'student_email',
            ]);
        }

        return view('enrollease');
    }

    private function hasValidSsoSession(Request $request): bool
    {
        return $request->session()->has('sso_id')
            && $request->session()->has('sso_role')
            && $request->session()->has('sso_email');
    }

    /**
     * POST|GET /sso/redirect
     * Legacy handoff — mirrors EntryEase's ssoRedirect exactly.
     * Accepts portal-posted identity, stores in session, redirects to dashboard.
     */
    public function ssoRedirect(Request $request)
    {
        Log::info('[EnrollEase][SSO] Legacy ssoRedirect called', [
            'referer' => $request->header('referer'),
            'role'    => $request->input('role'),
            'id'      => $request->input('id'),
            'accept'  => $request->header('accept'),
        ]);

        $newRole  = $this->normalizeRole($request->input('role', 'student'));
        $name     = $request->input('name',     '');
        $email    = $request->input('email',    '');
        $id       = $request->input('id',       '');
        $embedded = $request->input('embedded') === '1';

        $wantsJson = $request->wantsJson()
            || str_contains($request->header('accept') ?? '', 'application/json');

        // Role-change detection — redirect back to referrer if same host
        $previousRole = $request->session()->get('sso_role');
        $isRoleChange = $previousRole && $previousRole !== $newRole;
        $referrer     = $request->header('referer');

        // Store session
        $request->session()->put([
            'sso_id'               => $id,
            'sso_role'             => $newRole,
            'sso_name'             => $name,
            'sso_email'            => $email,
            'sso_embedded'         => $embedded,
            'student_email'        => $email,
            'user'                 => compact('id', 'name', 'email') + ['role' => $newRole],
            'sso_authenticated_at' => now()->timestamp,
        ]);

        Log::info('[EnrollEase][SSO] Legacy session created', [
            'sso_id'         => $id,
            'role'           => $newRole,
            'embedded'       => $embedded,
            'is_role_change' => $isRoleChange,
        ]);

        // Role change → redirect back to same page
        if ($isRoleChange && $referrer) {
            $referrerHost = parse_url($referrer, PHP_URL_HOST);
            if ($referrerHost === $request->getHost()) {
                return redirect()->away($referrer);
            }
        }

        $userData = ['id' => $id, 'role' => $newRole, 'name' => $name, 'email' => $email];

        // JSON response (embedded XHR path — mirrors entryease.js fetch flow)
        if ($wantsJson) {
            return response()->json([
                'redirect' => $this->dashboardUrl($newRole),
                'role'     => $newRole,
                'user'     => $userData,
            ]);
        }

        // Server-rendered dashboard (form submit path)
        return $this->renderDashboard($newRole, $id, $name, $email);
    }

    /**
     * POST|GET /logout
     */
    public function logout(Request $request)
    {
        $embedded = $request->session()->get('sso_embedded', false);

        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('[EnrollEase][SSO] User logged out', ['embedded' => $embedded]);

        $redirectUrl = $request->input('redirect')
            ?? config('app.portal_url', 'https://deoris.test');

        if ($embedded || $request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Logged out successfully',
                'redirect' => $redirectUrl,
                'embedded' => $embedded,
            ]);
        }

        return redirect($redirectUrl);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function renderDashboard(string $role, string $id, string $name, string $email)
    {
        $student = (object) ['id' => $id, 'name' => $name, 'email' => $email, 'role' => $role];

        if ($role === 'student') {
            $enrollment = $email
                ? Enrollment::with('room')->where('email', $email)->latest()->first()
                : null;

            return view('enrollment.student.dashboard', [
                'student'    => $student,
                'enrollment' => $enrollment,
            ]);
        }

        if ($role === 'officer') {
            return view('enrollment.officer.dashboard', [
                'role'               => 'officer',
                'totalEnrollments'   => Enrollment::count(),
                'pendingCount'       => Enrollment::where('status', 'pending')->count(),
                'verifiedCount'      => Enrollment::where('status', Enrollment::STATUS_REVIEWING)->count(),
                'reviewingCount'     => Enrollment::where('status', Enrollment::STATUS_REVIEWING)->count(),
                'approvedCount'      => Enrollment::where('status', Enrollment::STATUS_APPROVED)->count(),
                'enrolledCount'      => Enrollment::where('status', Enrollment::STATUS_ENROLLED)->count(),
                'processedToday'     => Enrollment::whereIn('status', [
                                            Enrollment::STATUS_APPROVED,
                                            Enrollment::STATUS_REJECTED,
                                        ])->whereDate('updated_at', now()->toDateString())->count(),
                'pendingEnrollments' => Enrollment::where('status', 'pending')->latest()->limit(10)->get(),
            ]);
        }

        if ($role === 'admin') {
            return view('enrollment.admin.dashboard', [
                'role'              => 'admin',
                'totalEnrollments'  => Enrollment::count(),
                'pendingCount'      => Enrollment::where('status', 'pending')->count(),
                'verifiedCount'     => Enrollment::where('status', Enrollment::STATUS_REVIEWING)->count(),
                'reviewingCount'    => Enrollment::where('status', Enrollment::STATUS_REVIEWING)->count(),
                'approvedCount'     => Enrollment::where('status', Enrollment::STATUS_APPROVED)->count(),
                'enrolledCount'     => Enrollment::where('status', Enrollment::STATUS_ENROLLED)->count(),
                'rejectedCount'     => Enrollment::where('status', Enrollment::STATUS_REJECTED)->count(),
                'roomCount'         => \App\Models\Room::count(),
                'recentEnrollments' => Enrollment::with('room')->latest()->limit(10)->get(),
            ]);
        }

        Log::warning('[EnrollEase][SSO] Unknown role in ssoRedirect', ['role' => $role]);
        return redirect('/');
    }

    private function dashboardUrl(string $role): string
    {
        return match ($role) {
            'admin'   => route('admin.dashboard'),
            'officer' => route('officer.dashboard'),
            default   => route('student.dashboard'),
        };
    }

    private function normalizeRole(?string $role): string
    {
        return match ($role) {
            'hr', 'officer', 'admission_officer' => 'officer',
            'admin'                               => 'admin',
            default                               => 'student',
        };
    }
}
