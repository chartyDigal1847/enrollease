<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EventOutbox extends Model
{
    protected $table = 'event_outbox';

    protected $fillable = [
        'event_id',
        'event_name',
        'source_module',
        'correlation_id',
        'payload',
        'schema_version',
        'status',
        'attempts',
        'last_error',
        'published_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * Queue an event for publishing to the DEORIS Event Hub.
     */
    public static function queue(
        string $eventName,
        array $payload,
        ?string $correlationId = null,
        string $schemaVersion = '1.0',
    ): self {
        return self::create([
            'event_id'       => (string) Str::uuid(),
            'event_name'     => $eventName,
            'source_module'  => 'EnrollEase',
            'correlation_id' => $correlationId ?? (string) Str::uuid(),
            'payload'        => $payload,
            'schema_version' => $schemaVersion,
            'status'         => 'pending',
            'attempts'       => 0,
        ]);
    }

    public function markPublished(): void
    {
        $this->update(['status' => 'published', 'published_at' => now()]);
    }

    public function markFailed(string $error): void
    {
        $this->increment('attempts');
        $this->update(['status' => 'failed', 'last_error' => $error]);
    }

    public function markCancelled(string $reason = ''): void
    {
        $this->update(['status' => 'cancelled', 'last_error' => $reason]);
    }

    public function retry(): void
    {
        $this->update(['status' => 'pending', 'last_error' => null]);
    }
}
