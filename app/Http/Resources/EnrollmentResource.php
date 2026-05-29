<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'reference'      => '#' . str_pad($this->id, 4, '0', STR_PAD_LEFT),
            'student' => [
                'id'     => $this->student_id,
                'name'   => $this->student_name,
                'email'  => $this->email,
                'gender' => $this->gender,
                'lrn'    => $this->lrn,
            ],
            'academic' => [
                'grade_level'          => $this->grade_level,
                'school_year'          => $this->school_year,
                'previous_school'      => $this->previous_school,
                'last_grade_completed' => $this->last_grade_completed,
                'average_grade'        => $this->average_grade,
            ],
            'section' => $this->when($this->room, fn () => [
                'id'      => $this->room->id,
                'name'    => $this->room->name,
                'section' => $this->room->section,
                'adviser' => $this->room->adviser,
            ]),
            'academic_term' => $this->when($this->academicTerm, fn () => [
                'id'          => $this->academicTerm->id,
                'name'        => $this->academicTerm->name,
                'school_year' => $this->academicTerm->school_year,
            ]),
            'status'     => $this->status,
            'remarks'    => $this->remarks,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
