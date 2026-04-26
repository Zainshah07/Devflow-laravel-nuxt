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
// use Illuminate\Http\Resources\JsonResponse;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Controllers\Api\StatsController;
use App\Services\TaskCacheService;
use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Events\TaskStatusChanged;
use App\Events\TaskUpdated; 



class TaskController extends Controller
{
     public function __construct(
        private readonly TaskCacheService $cache
    ) {}

 public function index(Request $request, Project $project)
{
    $this->authorize('view', $project);

    $cacheKey = $this->cache->buildKey($project->id, $request->query());

    $cachedData = $this->cache->remember(
        $cacheKey,
        $project->id,
        function () use ($request, $project) {
            $query = QueryBuilder::for(Task::class)
                ->allowedFilters(
                    AllowedFilter::exact('status'),
                    AllowedFilter::exact('priority'),
                    AllowedFilter::scope('search')
                )
                // ✅ FIXED: Removed the array brackets []
                ->allowedIncludes('assignees', 'creator', 'project') 
                ->where('project_id', $project->id)
                ->with(['assignees', 'creator'])
                ->defaultSort('-created_at')
                ->cursorPaginate(20);

            // Return primitive array to avoid serialization issues in Redis
            return [
                'data' => TaskResource::collection($query->getCollection())->resolve(),
                'next_cursor' => optional($query->nextCursor())->encode(),
                'prev_cursor' => optional($query->previousCursor())->encode(),
            ];
        }
    );

    return response()->json([
        'data' => $cachedData['data'],
        'meta' => [
            'next_cursor' => $cachedData['next_cursor'],
            'prev_cursor' => $cachedData['prev_cursor'],
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

        // DSA — Observer pattern: broadcast to all project channel subscribers
    // toOthers() excludes the requesting socket so the sender
    // does not receive a duplicate update (they already updated optimistically)

    broadcast(new TaskCreated($task->load(['assignees', 'creator', 'project'])))
        ->toOthers();

        $task->load(['assignees', 'creator', 'project']);


        return response()->json([
            'success'  => true,
            'message'  => 'Task created successfully',
            'data'     => new TaskResource($task),
        ], 201);
    }

    public function show(Request $request, Project $project, Task $task)
    {
        $this->authorize('view', $project);

        $task->load(['assignees', 'creator', 'project']);

        return response()->json([
            'success'  => true,
            'message'  => 'Task fetched successfully',
            'data'     => new TaskResource($task),
        ], 200);
    }

    public function update(UpdateTaskRequest $request, Project $project, Task $task): JsonResponse|TaskResource
    {
        $this->authorize('update', $task->project);

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
        $oldStatus = $task->status->value;
        $task->update($request->validated());
        // Invalidate cache after every write
        $this->cache->invalidateProject($task->project_id);
        $statusChanged = $task->wasChanged('status');
        
        StatsController::invalidateCache($request->user()->id);
       $freshTask = $task->fresh()->load(['assignees', 'creator', 'project']);
        // Broadcast status change separately for targeted UI updates
        if ($statusChanged) {
            broadcast(new TaskStatusChanged(
                $freshTask,
                $oldStatus,
                $freshTask->status->value
            ))->toOthers();
        } else {
            broadcast(new TaskUpdated($freshTask))->toOthers();
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Task updated successfully',
            'data'     => new TaskResource($freshTask),
        ], 200);
    }

    public function destroy(Request $request, Project $project, Task $task){
        $this->authorize('view', $project);

        $task->delete();
          $this->cache->invalidateProject($project->id);
        StatsController::invalidateCache($request->user()->id);
        
        broadcast(new TaskDeleted($taskId, $projectId))->toOthers();

        return response()->json([
            'success'  => true,
            'message'  => 'Task deleted successfully',
        ], 204);
    }

}

    

