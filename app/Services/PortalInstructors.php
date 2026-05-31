<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class PortalInstructors
{
    public function all(): Collection
    {
        return Cache::remember('portal_instructors', now()->addMinutes(5), function () {
            try {
                return DB::connection('deoris')
                    ->table('users')
                    ->select(['id', 'name', 'email'])
                    ->where('role', 'instructor')
                    ->orderBy('name')
                    ->get();
            } catch (Throwable) {
                return collect();
            }
        });
    }
}
