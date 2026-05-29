<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/search
 * Federated search endpoint — used by DEORIS portal federated search.
 */
class SearchApiController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // Verify search token from DEORIS portal
        $token = $request->header('X-Search-Token')
            ?? $request->query('token');

        $expected = config('enrollease.search_token');

        if ($expected && $token !== $expected) {
            return response()->json(['success' => false, 'error' => 'unauthorized'], 401);
        }

        $q       = trim((string) $request->input('q', ''));
        $perPage = min((int) $request->integer('per_page', 10), 50);

        if (strlen($q) < 2) {
            return response()->json([
                'success' => true,
                'query'   => $q,
                'results' => [],
            ]);
        }

        $enrollments = Enrollment::where(fn ($b) =>
            $b->where('student_name', 'like', "%{$q}%")
              ->orWhere('email', 'like', "%{$q}%")
              ->orWhere('lrn', 'like', "%{$q}%")
        )
        ->latest()
        ->limit($perPage)
        ->get()
        ->map(fn ($e) => [
            'type'        => 'enrollment',
            'id'          => $e->id,
            'title'       => $e->student_name,
            'subtitle'    => "Grade {$e->grade_level} — " . ucfirst($e->status),
            'meta'        => $e->email,
            'url'         => '/officer/enrollments/' . $e->id,
            'badge'       => $e->status,
            'badge_color' => match ($e->status) {
                'enrolled'  => 'green',
                'approved'  => 'blue',
                'pending'   => 'yellow',
                'rejected'  => 'red',
                default     => 'gray',
            },
        ]);

        $sections = Room::where('name', 'like', "%{$q}%")
            ->orWhere('section', 'like', "%{$q}%")
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'type'     => 'section',
                'id'       => $r->id,
                'title'    => $r->name . ($r->section ? " — {$r->section}" : ''),
                'subtitle' => "Grade {$r->grade_level}" . ($r->adviser ? " · {$r->adviser}" : ''),
                'meta'     => null,
                'url'      => '/officer/rooms',
                'badge'    => 'section',
                'badge_color' => 'purple',
            ]);

        return response()->json([
            'success' => true,
            'query'   => $q,
            'results' => $enrollments->concat($sections)->values(),
        ]);
    }
}
