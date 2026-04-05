<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;


class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $projects = $user->projects()->withCount('tasks')
            ->with('owner')
            ->latest()
            ->get();
        return response()->json([
            'success'  => true,
            'message'  => 'Projects fetched successfully',
            'projects' => ProjectResource::collection($projects),
        ], 200);
    }

    public function store(StoreProjectRequest $request){
        $user = $request->user();
        $project = $user->projects()->create($request->validated());

        return response()->json([
            'success'  => true,
            'message'  => 'Project created successfully',
            'project'  => ProjectResource::single($project),
        ], 201);
    }

    public function show(Request $request, Project $project){
        // Policy check: only the project owner can view it
        // DSA: this is a graph membership check — does this user node
        // have an edge (ownership) to this project node?
        $this->authorize('view', $project);
        $project->load(['owner', 'tasks.assignees', 'tasks.creator']);

        return response()->json([
            'success'  => true,
            'message'  => 'Project fetched successfully',
            'project'  => ProjectResource::single($project),
        ], 200);
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return response()->json([
            'success'  => true,
            'message'  => 'Project updated successfully',
            'project'  => ProjectResource::single($project),
        ], 200);
    }
    public function destroy(Request $request, Project $project): ProjectResource
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json([
            'success'  => true,
            'message'  => 'Project deleted successfully',
        ], 200);
    }

}
