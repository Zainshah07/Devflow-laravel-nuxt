<?php

namespace App\Http\Controllers\api;


use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectMemberController extends Controller
{
    // List all members of a project
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        // Combine owner + members in one response
        $owner   = collect([$project->owner->load([])])->map(fn ($u) => [
            'id'   => $u->id,
            'name' => $u->name,
            'email'=> $u->email,
            'role' => 'owner',
        ]);

        $members = $project->members()->get()->map(fn ($u) => [
            'id'   => $u->id,
            'name' => $u->name,
            'email'=> $u->email,
            'role' => $u->pivot->role,
        ]);

        return response()->json([
            'data' => $owner->merge($members)->values(),
        ]);
    }

    // Invite a user to a project
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role'  => ['sometimes', 'in:member,viewer'],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        // Cannot invite yourself (you are already the owner)
        if ($user->id === $project->user_id) {
            return response()->json([
                'message' => 'You are already the owner of this project.',
            ], 422);
        }

        // Cannot invite someone who is already a member
        if ($project->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'This user is already a member of this project.',
            ], 422);
        }

        $project->members()->attach($user->id, [
            'role' => $validated['role'] ?? 'member',
        ]);

        return response()->json([
            'data'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $validated['role'] ?? 'member',
            ],
            'message' => "{$user->name} has been added to the project.",
        ], 201);
    }

    // Remove a member from a project
    public function destroy(Request $request, Project $project, User $user): JsonResponse
    {
        $this->authorize('update', $project);

        // Cannot remove the owner
        if ($user->id === $project->user_id) {
            return response()->json([
                'message' => 'Cannot remove the project owner.',
            ], 422);
        }

        $project->members()->detach($user->id);

        return response()->json(null, 204);
    }
}
