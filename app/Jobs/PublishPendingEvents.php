<?php

namespace App\Jobs;

use App\Services\EventPublisher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PublishPendingEvents implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 30;

    public function __construct()
    {
        $this->onQueue('events');
    }

    public function handle(EventPublisher $publisher): void
    {
        $count = $publisher->publishPending();
        Log::info("[EnrollEase][Jobs] PublishPendingEvents: published {$count} events.");
    }
}
