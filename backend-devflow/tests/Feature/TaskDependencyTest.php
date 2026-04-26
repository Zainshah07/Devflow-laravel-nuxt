<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('adds a valid task dependency', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $taskA = Task::factory()->create(['project_id' => $project->id, 'created_by' => $user->id]);
    $taskB = Task::factory()->create(['project_id' => $project->id, 'created_by' => $user->id]);

    $this->actingAs($user)
        ->postJson("/api/tasks/{$taskA->id}/dependencies", [
            'depends_on_task_id' => $taskB->id,
        ])
        ->assertStatus(201);
});

it('rejects circular dependency — DFS detects the cycle', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $taskA = Task::factory()->create(['project_id' => $project->id, 'created_by' => $user->id]);
    $taskB = Task::factory()->create(['project_id' => $project->id, 'created_by' => $user->id]);

    // A depends on B
    $this->actingAs($user)
        ->postJson("/api/tasks/{$taskA->id}/dependencies", [
            'depends_on_task_id' => $taskB->id,
        ])
        ->assertStatus(201);

    // B depends on A — would create A → B → A cycle
    $this->actingAs($user)
        ->postJson("/api/tasks/{$taskB->id}/dependencies", [
            'depends_on_task_id' => $taskA->id,
        ])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Adding this dependency would create a circular dependency.']);
});

it('rejects self dependency', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $task    = Task::factory()->create(['project_id' => $project->id, 'created_by' => $user->id]);

    $this->actingAs($user)
        ->postJson("/api/tasks/{$task->id}/dependencies", [
            'depends_on_task_id' => $task->id,
        ])
        ->assertStatus(422);
});