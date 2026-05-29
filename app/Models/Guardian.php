<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Guardian extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'name',
        'relationship',
        'contact_number',
        'email',
        'occupation',
    ];

    // ── Relationships ───────────────────────────────────────────────────────

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
