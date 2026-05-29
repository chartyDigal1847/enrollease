<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureSsoAuthenticated
 * Exact copy of EntryEase's middleware — adapted for EnrollEase paths.
 */
class EnsureSsoAuthenticated
{
    protected $except = [
        'api/sso/exchange',
        'api/sso/heartbeat',
        'sso/exchange',
        '/',
        'sso/redirect',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        if ($request->is('api/*')) {
            return $this->validateApiRequest($request, $next);
        }

        if (! $this->hasSsoContext($request)) {
            \Log::debug('[EnrollEase][SSO] No SSO context', [
                'path'       => $request->path(),
                'session_id' => $request->hasSession() ? $request->session()->getId() : 'none',
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            return redirect('/');
        }

        return $next($request);
    }

    protected function shouldSkip(Request $request): bool
    {
        foreach ($this->except as $except) {
            if ($request->is($except) || $request->fullUrlIs(route('home'))) {
                return true;
            }
        }
        return false;
    }

    protected function validateApiRequest(Request $request, Closure $next): Response
    {
        if ($request->is('api/sso/*')) {
            return $next($request);
        }

        if (! $this->hasSsoContext($request)) {
            return response()->json([
                'error'   => 'Unauthenticated',
                'message' => 'SSO authentication required.',
            ], 401);
        }

        return $next($request);
    }

    protected function hasSsoContext(Request $request): bool
    {
        try {
            $session = $request->session();
        } catch (\RuntimeException $e) {
            return false;
        }

        return $session->has('sso_id')
            && $session->has('sso_role')
            && $session->has('sso_email')
            && ! empty($session->get('sso_id'));
    }
}
