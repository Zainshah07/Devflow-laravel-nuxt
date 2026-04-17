<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId   = $request->user()->id;
        $cacheKey = 'stats:user:' . $userId;

        // Cache stats for 10 minutes — they don't need to be real-time
        $stats = Cache::remember($cacheKey, 600, function () use ($userId) {

            // ─────────────────────────────────────────────────────────
            // DSA — N+1 elimination (BFS level-order batching):
            // A single GROUP BY query counts tasks by status in one
            // SQL round trip. Without groupBy this would be:
            // - 1 query for total count
            // - 1 query per status = 4 additional queries
            // With groupBy: always 1 query regardless of status count.
            //
            // This is the same optimization as BFS fetching an entire
            // level at once instead of visiting each node individually.
            // ─────────────────────────────────────────────────────────
            $taskCounts = Task::where('created_by', $userId)
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $totalTasks    = array_sum($taskCounts);
            $completedTasks = $taskCounts['done'] ?? 0;
            $todoCounts     = $taskCounts['todo'] ?? 0;
            $inProgressCount = $taskCounts['in_progress'] ?? 0;

            // DSA — Array filter predicate with index:
            // overdueCount uses scopeOverdue which targets the
            // idx_tasks_due_date index — binary search first,
            // then predicate check.
            $overdueCount = Task::where('created_by', $userId)
                ->overdue()
                ->count();

            $completionRate = $totalTasks > 0
                ? round(($completedTasks / $totalTasks) * 100, 1)
                : 0;

            // Project count for this user — O(1) with index on user_id
            $projectCount = Project::where('user_id', $userId)->count();

            // Per-project completion — one query with aggregation
            // DSA: selectRaw with GROUP BY = partition array by key
            // and aggregate each partition simultaneously
            $projectStats = Project::where('user_id', $userId)
                ->select([
                    'projects.id',
                    'projects.name',
                    DB::raw('COUNT(tasks.id) as total_tasks'),
                    DB::raw("SUM(CASE WHEN tasks.status = 'done' THEN 1 ELSE 0 END) as done_tasks"),
                ])
                ->leftJoin('tasks', 'tasks.project_id', '=', 'projects.id')
                ->groupBy('projects.id', 'projects.name')
                ->get()
                ->map(fn ($p) => [
                    'id'              => $p->id,
                    'name'            => $p->name,
                    'total_tasks'     => (int) $p->total_tasks,
                    'done_tasks'      => (int) $p->done_tasks,
                    'completion_rate' => $p->total_tasks > 0
                        ? round(($p->done_tasks / $p->total_tasks) * 100, 1)
                        : 0,
                ]);

            return [
                'total_tasks'      => $totalTasks,
                'completed_tasks'  => $completedTasks,
                'todo_tasks'       => $todoCounts,
                'in_progress_tasks' => $inProgressCount,
                'overdue_tasks'    => $overdueCount,
                'completion_rate'  => $completionRate,
                'total_projects'   => $projectCount,
                'project_stats'    => $projectStats,
            ];
        });

        return response()->json(['data' => $stats]);
    }

    // Invalidate the stats cache when tasks change
    // Call this from TaskController on create/update/delete
    public static function invalidateCache(int $userId): void
    {
        Cache::forget('stats:user:' . $userId);
    }
}