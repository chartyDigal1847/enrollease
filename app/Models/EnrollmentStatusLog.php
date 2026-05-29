<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnrollmentStatusLog extends Model
{
    protected $fillable = [
        'enrollment_id',
        'from_status',
        'to_status',
        'changed_by_role',
        'changed_by_id',
        'changed_by_name',
        'remarks',
        'ip_address',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Record a status change from application code (supplements the DB trigger).
     * The trigger handles DB-level changes; this handles app-level context (who, why).
     */
    public static function record(
        int $enrollmentId,
        ?string $fromStatus,
        string $toStatus,
        ?string $remarks = null,
        ?string $role = null,
        ?string $actorId = null,
        ?string $actorName = null,
        ?string $ip = null,
    ): self {
        return self::create([
            'enrollment_id'    => $enrollmentId,
            'from_status'      => $fromStatus,
            'to_status'        => $toStatus,
            'changed_by_role'  => $role ?? session('sso_role', 'system'),
            'changed_by_id'    => $actorId ?? session('sso_id'),
            'changed_by_name'  => $actorName ?? session('sso_name'),
            'remarks'          => $remarks,
            'ip_address'       => $ip ?? request()->ip(),
        ]);
    }
}
