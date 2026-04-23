<?php

use App\Models\Project;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('project.{projectId}', function ($user, int $projectId) {
    $project = Project::find($projectId);

    if (!$project) {
        return false;
    }

    return $user->id === $project->user_id
        ? ['id' => $user->id, 'name' => $user->name]
        : false;
});

Broadcast::channel('presence.project.{projectId}', function ($user, int $projectId) {
    $project = Project::find($projectId);

    if (!$project || $user->id !== $project->user_id) {
        return false;
    }

    return [
        'id'   => $user->id,
        'name' => $user->name,
    ];
});