<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\AnonymousResourceCollection;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\Project;


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
}
