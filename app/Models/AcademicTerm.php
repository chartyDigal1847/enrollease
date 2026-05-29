<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademicTerm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'school_year',
        'semester',
        'enrollment_start',
        'enrollment_end',
        'is_active',
    ];

    protected $casts = [
        'enrollment_start' => 'date',
        'enrollment_end'   => 'date',
        'is_active'        => 'boolean',
    ];

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public static function active(): ?self
    {
        return self::where('is_active', true)->latest()->first();
    }

    public function isEnrollmentOpen(): bool
    {
        $today = now()->toDateString();
        return $this->is_active
            && $today >= $this->enrollment_start->toDateString()
            && $today <= $this->enrollment_end->toDateString();
    }
}
