<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'deoris_user_id',
        'student_name',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'gender',
        'nationality',
        'email',
        'contact_number',
        'address',
        'lrn',
        'previous_school',
        'last_grade_completed',
        'average_grade',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    // ── Relationships ───────────────────────────────────────────────────────

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function guardians()
    {
        return $this->hasMany(Guardian::class);
    }

    public function documents()
    {
        return $this->hasMany(StudentDocument::class);
    }
}
