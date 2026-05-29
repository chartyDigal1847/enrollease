<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| EnrollEase Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Publish pending outbox events every minute
Schedule::command('enrollease:publish-events')->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Clean up expired SSO tokens every 10 minutes
Schedule::call(fn () => \App\Models\SsoToken::cleanupExpired())
    ->everyTenMinutes()
    ->name('sso-token-cleanup')
    ->withoutOverlapping();
