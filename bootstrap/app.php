<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

/**
 * bootstrap/app.php
 * Exact copy of EntryEase's bootstrap/app.php — adapted for EnrollEase.
 */
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        api:      __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'enrollease.role' => \App\Http\Middleware\EnsureEnrollEaseRole::class,
            'sso.required'    => \App\Http\Middleware\EnsureSsoAuthenticated::class,
        ]);

        // Apply SSO auth to web routes AFTER session middleware initializes
        // Mirrors EntryEase exactly
        $middleware->web(append: [
            \App\Http\Middleware\EnsureSsoAuthenticated::class,
            \App\Http\Middleware\TrackNavigationHistory::class,
        ]);

        // API routes need session for SSO session-based auth
        $middleware->api(append: [
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        // Pin session cookie config before session starts — prevents XAMPP
        // cross-vhost contamination from bleeding another module's cookie name/SameSite.
        $middleware->prependToGroup('web', \App\Http\Middleware\ForceSessionCookies::class);
        $middleware->prependToGroup('api', \App\Http\Middleware\ForceSessionCookies::class);

        $middleware->validateCsrfTokens(except: [
            'api/sso/*',    // SSO endpoints don't use CSRF
            'sso/redirect', // Legacy SSO redirect
            'sso/exchange', // Token exchange — CSRF not applicable (token-based)
        ]);

        // CSP — allows portal to embed this module in an iframe
        $middleware->append(\App\Http\Middleware\ModuleCspMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error'   => 'unauthenticated',
                    'message' => 'SSO authentication required.',
                ], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error'   => 'forbidden',
                    'message' => $e->getMessage() ?: 'You do not have permission.',
                ], 403);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error'   => 'validation_failed',
                    'message' => 'The given data was invalid.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error'   => 'not_found',
                    'message' => 'The requested resource was not found.',
                ], 404);
            }
        });

        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error'   => 'too_many_requests',
                    'message' => 'Too many requests. Please slow down.',
                ], 429);
            }
        });

    })->create();
