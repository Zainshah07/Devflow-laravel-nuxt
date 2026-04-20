<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\Project;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Enums\TaskStatus;
use Illuminate\Http\Resources\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Controllers\Api\StatsController;
use App\Services\TaskCacheService;



class TaskController extends Controller
{
     public function __construct(
        private readonly TaskCacheService $cache
    ) {}

    public function index(Request $request, Project $project)
{
    $this->authorize('view', $project);
            // ─────────────────────────────────────────────────────────────
        // DSA — Hash Map cache-aside pattern:
        // 1. Build the cache key from project ID + query params hash
        // 2. Check Redis — if key exists return it immediately (O(1))
        // 3. On miss: run the full DB query, store in Redis, return
        //
        // The cache key includes all query params so different filter
        // combinations each get their own cache entry — same concept
        // as using a composite key in a hash map problem.
        // ─────────────────────────────────────────────────────────────

        $cacheKey = $this->cache->buildKey($project->id, $request->query());

        // ─────────────────────────────────────────────────────────────
        // DSA — Binary Search in action:
        // allowedSorts maps to ORDER BY clauses that use B-tree indexes.
        // Sorting by due_date uses idx_tasks_due_date — O(log n) seek.
        // Without the index MySQL would sort all rows in memory — O(n log n).
        //
        // DSA — Hash Map lookup:
        // AllowedFilter::scope('search') calls scopeSearch which uses
        // MATCH AGAINST — the FULLTEXT inverted index hash map.
        //
        // DSA — Cursor Pagination O(log n) vs O(n):
        // cursorPaginate uses the primary key index to jump to the
        // correct starting row. Offset-based paginate scans and
        // discards all previous rows on every page request.
        // ─────────────────────────────────────────────────────────────

    $tasks = $this->cache->remember(
        $cacheKey,
        $project->id,
        function () use ($request, $project) {

            $query = QueryBuilder::for(Task::class)
                // ✅ FIXED (variadic arguments — Spatie v4/v5 change)
                ->allowedFilters(
                    AllowedFilter::exact('status'),
                    AllowedFilter::exact('priority'),
                    AllowedFilter::scope('search')
                )
                ->allowedSorts(
                    AllowedSort::field('created_at'),
                    AllowedSort::field('due_date'),
                    AllowedSort::field('priority'),
                    AllowedSort::field('title')
                )
                ->allowedIncludes('assignees', 'creator', 'project')
                ->where('project_id', $project->id)
                ->with(['assignees', 'creator'])
                ->defaultSort('-created_at')
                ->cursorPaginate(20);

            // ✅ CRITICAL FIX: never cache paginator object
            return [
                'data' => $query->items(),
                'next_cursor' => optional($query->nextCursor())->encode(),
                'prev_cursor' => optional($query->previousCursor())->encode(),
            ];
        }
    );

    // ✅ Convert back to paginator-style API response
    return TaskResource::collection(collect($tasks['data']))
        ->additional([
            'meta' => [
                'next_cursor' => $tasks['next_cursor'],
                'prev_cursor' => $tasks['prev_cursor'],
            ]
        ]);
}

    public function store(StoreTaskRequest $request, Project $project)
    {
        $this->authorize('view', $project);

         $task = $project->tasks()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'status'     => TaskStatus::Todo->value,
        ]);

         // Invalidate the task list cache for this project
        // DSA — bulk delete by tag: O(1) tag flush regardless of
        // how many individual cache entries exist for this project
        $this->cache->invalidateProject($project->id);
        StatsController::invalidateCache($request->user()->id);
        $task->load(['assignees', 'creator', 'project']);


        return response()->json([
            'success'  => true,
            'message'  => 'Task created successfully',
            'data'     => TaskResource::single($task),
        ], 201);
    }

    public function show(Request $request, Project $project, Task $task)
    {
        $this->authorize('view', $project);

        $task->load(['assignees', 'creator', 'project']);

        return response()->json([
            'success'  => true,
            'message'  => 'Task fetched successfully',
            'data'     => TaskResource::single($task),
        ], 200);
    }

    public function update(UpdateTaskRequest $request, Project $project, Task $task): JsonResponse|TaskResource
    {
        $this->authorize('view', $project);

        // DSA — Directed Graph traversal:
        // The status field represents a node in a directed graph.
        // Todo → InProgress → Done are the only valid directed edges.
        // canTransitionTo() checks whether the requested edge exists.
        // This is identical to checking adjacency in a graph:
        // "Is there a directed edge from currentNode to requestedNode?"
        // If not, we reject — same as returning false in Course Schedule.
        if ($request->has('status')) {
            $currentStatus = $task->status;
            $requestedStatus = TaskStatus::from($request->validated('status'));

            if (!$currentStatus->canTransitionTo($requestedStatus)) {
                return response()->json([
                    'message' => "Invalid transition: cannot move from '{$currentStatus->value}' to '{$requestedStatus->value}'.",
                    'errors'  => [
                        'status' => [
                            "Tasks can only move: todo → in_progress → done.",
                        ],
                    ],
                ], 422);
            }
        }
        $task->update($request->validated());
        // Invalidate cache after every write
        $this->cache->invalidateProject($project->id);
        
        StatsController::invalidateCache($request->user()->id);
        $task->fresh()->load(['assignees', 'creator', 'project']);

        return response()->json([
            'success'  => true,
            'message'  => 'Task updated successfully',
            'data'     => TaskResource::single($task),
        ], 200);
    }

    public function destroy(Request $request, Project $project, Task $task){
        $this->authorize('view', $project);

        $task->delete();
          $this->cache->invalidateProject($project->id);
        StatsController::invalidateCache($request->user()->id);

        return response()->json([
            'success'  => true,
            'message'  => 'Task deleted successfully',
        ], 204);
    }

}

    

