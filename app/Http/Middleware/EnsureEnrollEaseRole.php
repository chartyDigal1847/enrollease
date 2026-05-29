<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureEnrollEaseRole
 * Exact copy of EntryEase's EnsureEntryEaseRole — adapted for EnrollEase roles.
 *
 * Role map (mirrors portal role names → EnrollEase internal roles):
 *   hr | officer | admission_officer  → officer
 *   admin                             → admin
 *   *                                 → student
 */
class EnsureEnrollEaseRole
{
    private function normalizeRole(?string $role): string
    {
        return match ($role) {
            'hr', 'officer', 'admission_officer' => 'officer',
            'admin'                               => 'admin',
            default                               => 'student',
        };
    }

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $this->normalizeRole(
            session('sso_role')
            ?? data_get(session('user'), 'role')
            ?? 'student'
        );

        $allowed = array_map(
            fn(string $r) => $this->normalizeRole($r),
            $roles
        );

        if (! in_array($role, $allowed, true)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            abort(403, 'You do not have access to this EnrollEase area.');
        }

        return $next($request);
    }
}
