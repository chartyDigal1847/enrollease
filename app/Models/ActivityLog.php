<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'action',
        'subject_type',
        'subject_id',
        'actor_id',
        'actor_name',
        'actor_role',
        'context',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    /**
     * Log an action from anywhere in the application.
     */
    public static function log(
        string $action,
        mixed $subject = null,
        array $context = [],
    ): self {
        return self::create([
            'action'       => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'actor_id'     => session('sso_id'),
            'actor_name'   => session('sso_name'),
            'actor_role'   => session('sso_role'),
            'context'      => $context ?: null,
            'ip_address'   => request()->ip(),
            'user_agent'   => substr(request()->userAgent() ?? '', 0, 500),
        ]);
    }
}
