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


class TaskController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $tasks = $project->tasks()
            ->with(['assignees', 'creator'])
            ->latest()
            ->get();

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request, Project $project)
    {
        $this->authorize('view', $project);

         $task = $project->tasks()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'status'     => TaskStatus::Todo->value,
        ]);
        $task->load(['assignees', 'creator', 'project']);


        return response()->json([
            'success'  => true,
            'message'  => 'Task created successfully',
            'task'     => TaskResource::single($task),
        ], 201);
    }

    public function show(Request $request, Project $project, Task $task)
    {
        $this->authorize('view', $project);

        $task->load(['assignees', 'creator', 'project']);

        return response()->json([
            'success'  => true,
            'message'  => 'Task fetched successfully',
            'task'     => TaskResource::single($task),
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
        $task->load(['assignees', 'creator', 'project']);

        return response()->json([
            'success'  => true,
            'message'  => 'Task updated successfully',
            'task'     => TaskResource::single($task),
        ], 200);
    }

    public function destroy(){
        $this->authorize('view', $project);

        $task->delete();

        return response()->json([
            'success'  => true,
            'message'  => 'Task deleted successfully',
        ], 204);
    }

}

    

