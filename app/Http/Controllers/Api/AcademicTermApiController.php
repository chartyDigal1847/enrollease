<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicTermResource;
use App\Models\AcademicTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET  /api/v1/academic-terms
 * GET  /api/v1/academic-terms/active
 * POST /api/v1/academic-terms
 * PUT  /api/v1/academic-terms/{id}
 * DELETE /api/v1/academic-terms/{id}
 */
class AcademicTermApiController extends Controller
{
    /** GET /api/v1/academic-terms */
    public function index(): JsonResponse
    {
        $terms = AcademicTerm::orderByDesc('created_at')->get();
        return response()->json([
            'success' => true,
            'data'    => AcademicTermResource::collection($terms),
        ]);
    }

    /** GET /api/v1/academic-terms/active */
    public function active(): JsonResponse
    {
        $term = AcademicTerm::active();
        if (! $term) {
            return response()->json(['success' => false, 'message' => 'No active academic term.'], 404);
        }
        return response()->json(['success' => true, 'data' => new AcademicTermResource($term)]);
    }

    /** POST /api/v1/academic-terms */
    public function store(Request $request): JsonResponse
    {
        $this->requireRole(['admin', 'officer']);

        $data = $request->validate([
            'name'             => 'required|string|max:80',
            'school_year'      => 'required|string|max:20',
            'semester'         => 'required|in:1st,2nd,summer',
            'enrollment_start' => 'required|date',
            'enrollment_end'   => 'required|date|after:enrollment_start',
            'is_active'        => 'boolean',
        ]);

        // Deactivate others if this one is active
        if (! empty($data['is_active'])) {
            AcademicTerm::where('is_active', true)->update(['is_active' => false]);
        }

        $term = AcademicTerm::create($data);

        return response()->json([
            'success' => true,
            'data'    => new AcademicTermResource($term),
            'message' => 'Academic term created.',
        ], 201);
    }

    /** PUT /api/v1/academic-terms/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->requireRole(['admin', 'officer']);

        $term = AcademicTerm::findOrFail($id);
        $data = $request->validate([
            'name'             => 'sometimes|string|max:80',
            'school_year'      => 'sometimes|string|max:20',
            'semester'         => 'sometimes|in:1st,2nd,summer',
            'enrollment_start' => 'sometimes|date',
            'enrollment_end'   => 'sometimes|date',
            'is_active'        => 'boolean',
        ]);

        if (! empty($data['is_active'])) {
            AcademicTerm::where('is_active', true)->where('id', '!=', $id)->update(['is_active' => false]);
        }

        $term->update($data);

        return response()->json([
            'success' => true,
            'data'    => new AcademicTermResource($term),
            'message' => 'Academic term updated.',
        ]);
    }

    /** DELETE /api/v1/academic-terms/{id} */
    public function destroy(int $id): JsonResponse
    {
        $this->requireRole(['admin']);

        AcademicTerm::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Academic term deleted.']);
    }

    private function requireRole(array $roles): void
    {
        if (! in_array(session('sso_role'), $roles)) {
            abort(403, 'Insufficient permissions.');
        }
    }
}
