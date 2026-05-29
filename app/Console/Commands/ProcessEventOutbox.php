<?php

namespace App\Console\Commands;

use App\Services\EventPublisher;
use Illuminate\Console\Command;

class ProcessEventOutbox extends Command
{
    protected $signature   = 'enrollease:publish-events {--limit=50 : Max events to process per run}';
    protected $description = 'Publish pending events from the outbox to the DEORIS Event Hub.';

    public function handle(EventPublisher $publisher): int
    {
        $count = $publisher->publishPending();

        $this->info("[EnrollEase] Published {$count} event(s) to DEORIS Event Hub.");

        return self::SUCCESS;
    }
}
