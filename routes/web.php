<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EnrollEaseController;
use App\Http\Controllers\Admin\EnrollmentController as AdminEnrollmentController;
use App\Http\Controllers\Officer\EnrollmentController as OfficerEnrollmentController;
use App\Http\Controllers\Officer\RoomController as OfficerRoomController;
use App\Http\Controllers\Student\EnrollmentController as StudentEnrollmentController;

/*
|--------------------------------------------------------------------------
| EnrollEase Routes
|--------------------------------------------------------------------------
|
| Role responsibilities:
|   Student          — enroll, view own status
|   Admin            — monitor only (read-only dashboards, no write actions)
|   Admission Officer — full enrollment processing + room management
|
|--------------------------------------------------------------------------
*/

// ── Root — SSO shell
Route::get('/', [EnrollEaseController::class, 'index'])->name('home');

// ── Logout
Route::post('/logout', [EnrollEaseController::class, 'logout'])->name('logout');
Route::get('/logout',  [EnrollEaseController::class, 'logout'])->name('logout.get');

// ── SSO redirect
Route::post('/sso/redirect', [EnrollEaseController::class, 'ssoRedirect'])->name('sso.redirect');
Route::post('/sso/exchange', [EnrollEaseController::class, 'ssoExchange'])->name('sso.exchange.web');
Route::get('/sso/redirect',  [EnrollEaseController::class, 'ssoRedirect'])->name('sso.redirect.dev');

// ──────────────────────────────────────────────────────────────────────────────
// ADMIN — monitor only, no write actions
// ──────────────────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware('enrollease.role:admin')->group(function () {

    Route::get('/dashboard', [AdminEnrollmentController::class, 'dashboard'])
         ->name('dashboard');

    // Read-only enrollment browsing
    Route::prefix('enrollments')->name('enrollments.')->group(function () {
        Route::get('/',      [AdminEnrollmentController::class, 'index'])->name('index');
        Route::get('/{id}',  [AdminEnrollmentController::class, 'show']) ->name('show');
    });

    // Read-only room overview
    Route::get('/rooms',    [AdminEnrollmentController::class, 'rooms'])   ->name('rooms');
    Route::get('/students', [AdminEnrollmentController::class, 'students'])->name('students');
});

// ──────────────────────────────────────────────────────────────────────────────
// ADMISSION OFFICER — full enrollment processing + room management
// ──────────────────────────────────────────────────────────────────────────────
Route::prefix('officer')->name('officer.')->middleware('enrollease.role:officer,hr,admission_officer')->group(function () {

    Route::get('/dashboard', [OfficerEnrollmentController::class, 'dashboard'])
         ->name('dashboard');

    // Enrollment management
    Route::prefix('enrollments')->name('enrollments.')->group(function () {
        Route::get('/',                     [OfficerEnrollmentController::class, 'index'])       ->name('index');
        Route::get('/{id}',                 [OfficerEnrollmentController::class, 'show'])        ->name('show');
        Route::patch('/{id}/verify',        [OfficerEnrollmentController::class, 'verify'])      ->name('verify');
        Route::patch('/{id}/approve',       [OfficerEnrollmentController::class, 'approve'])     ->name('approve');
        Route::patch('/{id}/reject',        [OfficerEnrollmentController::class, 'reject'])      ->name('reject');
        Route::patch('/{id}/update-status', [OfficerEnrollmentController::class, 'updateStatus'])->name('updateStatus');
    });

    // Document viewing
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/{id}/{type}', [OfficerEnrollmentController::class, 'viewDocument'])->name('view');
    });

    // Room management (moved from admin)
    Route::prefix('rooms')->name('rooms.')->group(function () {
        Route::get('/',                [OfficerRoomController::class, 'index'])        ->name('index');
        Route::post('/',               [OfficerRoomController::class, 'store'])        ->name('store');
        Route::put('/{id}',            [OfficerRoomController::class, 'update'])       ->name('update');
        Route::delete('/{id}',         [OfficerRoomController::class, 'destroy'])      ->name('destroy');
        Route::post('/{id}/assign',    [OfficerRoomController::class, 'assign'])       ->name('assign');
        Route::patch('/{id}/capacity', [OfficerRoomController::class, 'capacity'])     ->name('capacity');
        Route::delete('/{id}/students/{enrollmentId}', [OfficerRoomController::class, 'removeStudent'])->name('removeStudent');
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// STUDENT — enroll + view status
// ──────────────────────────────────────────────────────────────────────────────
Route::prefix('student')->name('student.')->middleware('enrollease.role:student')->group(function () {

    Route::get('/dashboard', [StudentEnrollmentController::class, 'dashboard'])
         ->name('dashboard');

    Route::prefix('enrollment')->name('enrollment.')->group(function () {
        Route::get('/apply',  [StudentEnrollmentController::class, 'create'])->name('create');
        Route::post('/apply', [StudentEnrollmentController::class, 'store']) ->name('store');
        Route::get('/status', [StudentEnrollmentController::class, 'status'])->name('status');
    });
});
