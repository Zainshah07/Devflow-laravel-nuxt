<?php

use App\Models\Project;
use Illuminate\Support\Facades\Broadcast;

// Private channel for task events
Broadcast::channel('project.{projectId}', function ($user, int $projectId) {
    $project = Project::find($projectId);
    if (!$project) return false;
    if (!$project->hasMember($user->id)) return false;

    // Return user data — required for presence channels to work
    return ['id' => $user->id, 'name' => $user->name];
});