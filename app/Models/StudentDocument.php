<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'document_type',
        'file_path',
        'file_name',
    ];

    // ── Relationships ───────────────────────────────────────────────────────

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
