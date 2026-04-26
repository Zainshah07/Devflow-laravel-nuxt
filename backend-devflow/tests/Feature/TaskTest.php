<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a task under a project', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->id}/tasks", [
            'title'    => 'Fix login bug',
            'priority' => 'high',
        ]);

    $response
        ->assertStatus(201)
        ->assertJsonPath('data.title', 'Fix login bug')
        ->assertJsonPath('data.status', 'todo');

    $this->assertDatabaseHas('tasks', [
        'title'      => 'Fix login bug',
        'project_id' => $project->id,
        'created_by' => $user->id,
    ]);
});

it('rejects invalid status transition', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $task    = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $user->id,
        'status'     => TaskStatus::Todo->value,
    ]);

    // Todo → Done is an invalid transition (must go through in_progress)
    $this->actingAs($user)
        ->patchJson("/api/tasks/{$task->id}", ['status' => 'done'])
        ->assertStatus(422);
});

it('allows valid status transition', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $task    = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $user->id,
        'status'     => TaskStatus::Todo->value,
    ]);

    $this->actingAs($user)
        ->patchJson("/api/tasks/{$task->id}", ['status' => 'in_progress'])
        ->assertOk()
        ->assertJsonPath('data.status', 'in_progress');
});

it('prevents user B from accessing user A task', function () {
    $userA   = User::factory()->create();
    $userB   = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $userA->id]);
    $task    = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $userA->id,
    ]);

    $this->actingAs($userB)
        ->patchJson("/api/tasks/{$task->id}", ['title' => 'Hacked'])
        ->assertStatus(403);
});

it('rejects script tags in task title', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson("/api/projects/{$project->id}/tasks", [
            'title'    => '<script>alert(1)</script>',
            'priority' => 'high',
        ])
        ->assertStatus(422);
});