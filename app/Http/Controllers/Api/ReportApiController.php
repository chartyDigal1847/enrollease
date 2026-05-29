<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * GET /api/v1/reports/enrollment-stats
 * GET /api/v1/reports/section-capacity
 * GET /api/v1/reports/summary
 * GET /api/v1/reports/activity
 */
class ReportApiController extends Controller
{
    /** GET /api/v1/reports/enrollment-stats — uses v_enrollment_stats view */
    public function enrollmentStats(Request $request): JsonResponse
    {
        $query = DB::table('v_enrollment_stats');

        if ($request->filled('school_year')) {
            $query->where('school_year', $request->school_year);
        }
        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('school_year')->orderBy('grade_level')->get(),
        ]);
    }

    /** GET /api/v1/reports/section-capacity — uses v_section_capacity view */
    public function sectionCapacity(Request $request): JsonResponse
    {
        $query = DB::table('v_section_capacity');

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('grade_level')->orderBy('room_name')->get(),
        ]);
    }

    /** GET /api/v1/reports/summary — calls sp_enrollment_summary stored procedure */
    public function summary(Request $request): JsonResponse
    {
        $schoolYear = $request->input('school_year', date('Y') . '–' . (date('Y') + 1));

        $results = DB::select('CALL sp_enrollment_summary(?)', [$schoolYear]);

        return response()->json([
            'success'     => true,
            'school_year' => $schoolYear,
            'data'        => $results,
        ]);
    }

    /** GET /api/v1/reports/activity */
    public function activity(Request $request): JsonResponse
    {
        $query = DB::table('activity_logs')->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }
        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->actor_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $perPage = min((int) $request->integer('per_page', 20), 100);

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($perPage),
        ]);
    }

    /** GET /api/v1/reports/overview — dashboard numbers */
    public function overview(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'total'      => Enrollment::count(),
                'pending'    => Enrollment::where('status', 'pending')->count(),
                'reviewing'  => Enrollment::where('status', 'reviewing')->count(),
                'approved'   => Enrollment::where('status', 'approved')->count(),
                'enrolled'   => Enrollment::where('status', 'enrolled')->count(),
                'rejected'   => Enrollment::where('status', 'rejected')->count(),
                'cancelled'  => Enrollment::where('status', 'cancelled')->count(),
                'by_grade'   => Enrollment::select('grade_level', DB::raw('count(*) as total'))
                                    ->groupBy('grade_level')
                                    ->orderBy('grade_level')
                                    ->get(),
            ],
        ]);
    }
}
