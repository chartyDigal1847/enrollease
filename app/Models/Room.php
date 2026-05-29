<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'grade_level',
        'section',
        'adviser',
        'capacity_male',
        'capacity_female',
    ];

    // ── Relationships ───────────────────────────────────────────────────────

    public function students()
    {
        return $this->hasMany(Enrollment::class);
    }
}
