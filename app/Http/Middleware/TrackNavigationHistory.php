<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TrackNavigationHistory
 * Exact copy of EntryEase's TrackNavigationHistory.
 * Keeps a short session history of in-app GET pages so the back button
 * can return to the actual previous screen (works in iframes / SSO).
 */
class TrackNavigationHistory
{
    private const SESSION_KEY  = 'ee_nav_history';
    private const MAX_ENTRIES  = 30;

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldTrack($request)) {
            $this->updateStack($request);
        }

        return $next($request);
    }

    private function shouldTrack(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->ajax() || $request->expectsJson()) {
            return false;
        }

        $path = $request->path();

        if (str_starts_with($path, 'api/')
            || str_starts_with($path, 'sso/')
            || $path === ''
            || $path === 'up'
            || $path === 'logout') {
            return false;
        }

        return true;
    }

    private function updateStack(Request $request): void
    {
        $current = $request->fullUrl();
        $stack   = $request->session()->get(self::SESSION_KEY, []);

        if (! is_array($stack)) {
            $stack = [];
        }

        $index = array_search($current, $stack, true);
        if ($index !== false) {
            $stack = array_slice($stack, 0, $index + 1);
            $request->session()->put(self::SESSION_KEY, $stack);
            return;
        }

        if ($stack !== [] && end($stack) === $current) {
            return;
        }

        $stack[] = $current;

        if (count($stack) > self::MAX_ENTRIES) {
            $stack = array_slice($stack, -self::MAX_ENTRIES);
        }

        $request->session()->put(self::SESSION_KEY, $stack);
    }
}
