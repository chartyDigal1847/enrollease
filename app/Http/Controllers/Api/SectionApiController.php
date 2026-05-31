<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SectionResource;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * GET  /api/v1/sections
 * GET  /api/v1/sections/{id}
 * POST /api/v1/sections
 * PUT  /api/v1/sections/{id}
 * DELETE /api/v1/sections/{id}
 */
class SectionApiController extends Controller
{
    /** GET /api/v1/sections */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Room::withCount('students');

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return SectionResource::collection($query->orderBy('grade_level')->orderBy('name')->get());
    }

    /** GET /api/v1/sections/{id} */
    public function show(int $id): SectionResource
    {
        return new SectionResource(Room::withCount('students')->findOrFail($id));
    }

    /** GET /api/v1/sections/capacity — uses the MySQL view */
    public function capacity(Request $request): JsonResponse
    {
        $query = \DB::table('v_section_capacity');

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('grade_level')->orderBy('room_name')->get(),
        ]);
    }

    /** POST /api/v1/sections */
    public function store(Request $request): JsonResponse
    {
        $this->requireRole(['officer']);

        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'grade_level'      => 'required|integer|between:7,12',
            'section'          => 'nullable|string|max:80',
            'adviser'          => 'nullable|string|max:150',
            'capacity_male'    => 'nullable|integer|min:0',
            'capacity_female'  => 'nullable|integer|min:0',
        ]);

        $room = Room::create($data);

        return response()->json([
            'success' => true,
            'data'    => new SectionResource($room->loadCount('students')),
            'message' => 'Section created.',
        ], 201);
    }

    /** PUT /api/v1/sections/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->requireRole(['officer']);

        $room = Room::findOrFail($id);
        $data = $request->validate([
            'name'             => 'sometimes|string|max:100',
            'grade_level'      => 'sometimes|integer|between:7,12',
            'section'          => 'nullable|string|max:80',
            'adviser'          => 'nullable|string|max:150',
            'capacity_male'    => 'nullable|integer|min:0',
            'capacity_female'  => 'nullable|integer|min:0',
        ]);

        $room->update($data);

        return response()->json([
            'success' => true,
            'data'    => new SectionResource($room->loadCount('students')),
            'message' => 'Section updated.',
        ]);
    }

    /** DELETE /api/v1/sections/{id} */
    public function destroy(int $id): JsonResponse
    {
        $this->requireRole(['officer', 'admin']);

        $room = Room::findOrFail($id);
        $room->delete();

        return response()->json(['success' => true, 'message' => 'Section deleted.']);
    }

    private function requireRole(array $roles): void
    {
        if (! in_array(session('sso_role'), $roles)) {
            abort(403, 'Insufficient permissions.');
        }
    }
}
