<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskDependencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskDependencyController extends Controller
{
    public function __construct(
        private readonly TaskDependencyService $dependencyService
    ) {}

    /**
     * List all tasks that this task depends on.
     */
    public function index(Request $request, Task $task): AnonymousResourceCollection
    {
        $this->authorize('view', $task->project);

        return TaskResource::collection(
            $task->dependencies()->with(['project', 'assignees'])->get()
        );
    }

    /**
     * Add a dependency: $task depends on $dependency.
     *
     * DSA — Directed Graph edge insertion with cycle guard:
     * Before inserting edge (task → dependency), run DFS from
     * dependency back toward task. If task is reachable from
     * dependency via existing edges, this new edge creates a cycle.
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task->project);

        $validated = $request->validate([
            'depends_on_task_id' => [
                'required',
                'integer',
                'exists:tasks,id',
            ],
        ]);

        $dependsOnTaskId = (int) $validated['depends_on_task_id'];

        // Cannot depend on itself
        if ($task->id === $dependsOnTaskId) {
            return response()->json([
                'message' => 'A task cannot depend on itself.',
            ], 422);
        }

        // Check if this dependency already exists
        // DSA — O(log n) index lookup on unique constraint
        $alreadyExists = $task->dependencies()
            ->where('depends_on_task_id', $dependsOnTaskId)
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'message' => 'This dependency already exists.',
            ], 422);
        }

        // DSA — DFS cycle detection before inserting the edge:
        // "Would adding task → dependsOnTask create a cycle?"
        if ($this->dependencyService->wouldCreateCycle($task->id, $dependsOnTaskId)) {
            return response()->json([
                'message' => 'Adding this dependency would create a circular dependency.',
                'errors'  => [
                    'depends_on_task_id' => [
                        'Circular dependency detected. Task A cannot depend on Task B if Task B already depends on Task A (directly or transitively).',
                    ],
                ],
            ], 422);
        }

        // Safe to insert — attach the dependency edge
        $task->dependencies()->attach($dependsOnTaskId);

        return response()->json([
            'message' => 'Dependency added successfully.',
            'data'    => [
                'task_id'            => $task->id,
                'depends_on_task_id' => $dependsOnTaskId,
            ],
        ], 201);
    }

    /**
     * Remove a dependency edge from the graph.
     */
    public function destroy(Request $request, Task $task, Task $dependency): JsonResponse
    {
        $this->authorize('view', $task->project);

        $task->dependencies()->detach($dependency->id);

        return response()->json(null, 204);
    }

    /**
     * Get all tasks for a project with their dependency relationships.
     * Used by the frontend to render the dependency graph.
     *
     * DSA — Full graph export as adjacency list:
     * Returns every node (task) with its outgoing edges (dependencies).
     * The frontend uses this to build the visual graph.
     */
    public function projectGraph(Request $request, int $projectId): JsonResponse
    {
        $this->authorize('view', \App\Models\Project::findOrFail($projectId));

        // Load all tasks for the project with their dependency IDs
        // DSA: with('dependencies') eager loads the adjacency list
        // for all nodes in a single query — prevents N+1
        $tasks = Task::where('project_id', $projectId)
            ->with(['dependencies:id,title,status', 'assignees:id,name'])
            ->get();

        // Transform to graph format for the frontend
        // DSA: O(V + E) traversal — each task (V) and each dependency (E)
        $nodes = $tasks->map(fn ($task) => [
            'id'           => (string) $task->id,
            'title'        => $task->title,
            'status'       => $task->status->value,
            'priority'     => $task->priority->value,
            'is_overdue'   => $task->isOverdue(),
            'dependencies' => $task->dependencies->pluck('id')->map(fn($id) => (string)$id)->toArray(),
        ]);

        return response()->json(['data' => $nodes]);
    }
}