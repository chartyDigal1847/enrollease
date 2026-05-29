<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * ModuleCspMiddleware
 *
 * Sets Content-Security-Policy headers so the Deoris portal can embed
 * EnrollEase in an iframe.
 *
 * CRITICAL: frame-ancestors must include the portal URL or the iframe
 * will show a blank page — this is the #1 cause of blank iframes.
 *
 * Register in bootstrap/app.php:
 *   ->withMiddleware(function (Middleware $middleware): void {
 *       $middleware->append(\App\Http\Middleware\ModuleCspMiddleware::class);
 *   })
 */
class ModuleCspMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $portalUrl = config('app.portal_url', 'https://deoris.test');

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' " . $portalUrl . " https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com",
            "script-src-elem 'self' 'unsafe-inline' " . $portalUrl . " https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://unpkg.com",
            "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com https://unpkg.com",
            "img-src 'self' data:",
            "connect-src 'self' " . $portalUrl,
            "frame-ancestors " . $portalUrl,   // ← allows portal to iframe this module
            "frame-src 'self'",
            "object-src 'none'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
