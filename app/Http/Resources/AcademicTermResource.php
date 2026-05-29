<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicTermResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'school_year'      => $this->school_year,
            'semester'         => $this->semester,
            'enrollment_start' => $this->enrollment_start?->toDateString(),
            'enrollment_end'   => $this->enrollment_end?->toDateString(),
            'is_active'        => $this->is_active,
            'enrollment_open'  => $this->isEnrollmentOpen(),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
