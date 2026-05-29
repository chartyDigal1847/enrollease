<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'grade_level'      => $this->grade_level,
            'section'          => $this->section,
            'adviser'          => $this->adviser,
            'capacity_male'    => $this->capacity_male,
            'capacity_female'  => $this->capacity_female,
            'total_capacity'   => $this->capacity_male + $this->capacity_female,
            'enrolled_count'   => $this->students_count ?? $this->students->count(),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
