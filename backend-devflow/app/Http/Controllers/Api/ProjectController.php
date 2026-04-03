<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;


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
}
