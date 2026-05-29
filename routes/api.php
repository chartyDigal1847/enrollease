<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SsoController;
use App\Http\Controllers\Api\EnrollmentApiController;
use App\Http\Controllers\Api\SectionApiController;
use App\Http\Controllers\Api\AcademicTermApiController;
use App\Http\Controllers\Api\ReportApiController;
use App\Http\Controllers\Api\SearchApiController;
use App\Http\Controllers\Api\NotificationApiController;

/*
|--------------------------------------------------------------------------
| EnrollEase API Routes
|--------------------------------------------------------------------------
|
| Base URL: /api
|
| SSO endpoints:  /api/sso/*          — public, no session required
| v1 endpoints:   /api/v1/*           — SSO session required
|
*/

// ── SSO (public) ─────────────────────────────────────────────────────────────
Route::prefix('sso')->group(function () {
    Route::post('/exchange',  [SsoController::class, 'exchange']) ->name('sso.exchange');
    Route::get('/heartbeat',  [SsoController::class, 'heartbeat'])->name('sso.heartbeat');
    Route::post('/revoke',    [SsoController::class, 'revoke'])   ->name('sso.revoke');
});

// ── v1 API (SSO session required) ────────────────────────────────────────────
Route::prefix('v1')->name('api.v1.')->middleware(['throttle:api'])->group(function () {

    // ── Enrollments ───────────────────────────────────────────────────────
    Route::prefix('enrollments')->name('enrollments.')->group(function () {
        Route::get('/',                        [EnrollmentApiController::class, 'index'])        ->name('index');
        Route::post('/',                       [EnrollmentApiController::class, 'store'])        ->name('store');
        Route::get('/{id}',                    [EnrollmentApiController::class, 'show'])         ->name('show');
        Route::put('/{id}',                    [EnrollmentApiController::class, 'update'])       ->name('update');
        Route::delete('/{id}',                 [EnrollmentApiController::class, 'destroy'])      ->name('destroy');
        Route::get('/{id}/status-history',     [EnrollmentApiController::class, 'statusHistory'])->name('status-history');
        Route::get('/{id}/sync-status',        [EnrollmentApiController::class, 'syncStatus'])  ->name('sync-status');
    });

    // ── Sections (rooms) ──────────────────────────────────────────────────
    Route::prefix('sections')->name('sections.')->group(function () {
        Route::get('/capacity',  [SectionApiController::class, 'capacity'])->name('capacity');
        Route::get('/',          [SectionApiController::class, 'index'])   ->name('index');
        Route::post('/',         [SectionApiController::class, 'store'])   ->name('store');
        Route::get('/{id}',      [SectionApiController::class, 'show'])    ->name('show');
        Route::put('/{id}',      [SectionApiController::class, 'update'])  ->name('update');
        Route::delete('/{id}',   [SectionApiController::class, 'destroy']) ->name('destroy');
    });

    // ── Academic Terms ────────────────────────────────────────────────────
    Route::prefix('academic-terms')->name('academic-terms.')->group(function () {
        Route::get('/active',  [AcademicTermApiController::class, 'active']) ->name('active');
        Route::get('/',        [AcademicTermApiController::class, 'index'])  ->name('index');
        Route::post('/',       [AcademicTermApiController::class, 'store'])  ->name('store');
        Route::put('/{id}',    [AcademicTermApiController::class, 'update']) ->name('update');
        Route::delete('/{id}', [AcademicTermApiController::class, 'destroy'])->name('destroy');
    });

    // ── Reports ───────────────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/overview',          [ReportApiController::class, 'overview'])        ->name('overview');
        Route::get('/enrollment-stats',  [ReportApiController::class, 'enrollmentStats']) ->name('enrollment-stats');
        Route::get('/section-capacity',  [ReportApiController::class, 'sectionCapacity']) ->name('section-capacity');
        Route::get('/summary',           [ReportApiController::class, 'summary'])         ->name('summary');
        Route::get('/activity',          [ReportApiController::class, 'activity'])        ->name('activity');
    });

    // ── Notifications ─────────────────────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',              [NotificationApiController::class, 'index'])      ->name('index');
        Route::patch('/read-all',    [NotificationApiController::class, 'markAllRead'])->name('read-all');
        Route::patch('/{id}/read',   [NotificationApiController::class, 'markRead'])  ->name('read');
    });

    // ── Search (federated) ────────────────────────────────────────────────
    Route::get('/search', SearchApiController::class)->name('search');
});
