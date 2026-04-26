<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 401 for unauthenticated requests', function () {
    $this->getJson('/api/projects')->assertStatus(401);
});

it('returns only the authenticated users projects', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Project::factory(3)->create(['user_id' => $userA->id]);
    Project::factory(2)->create(['user_id' => $userB->id]);

    $response = $this->actingAs($userA)
        ->getJson('/api/projects');

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('creates a project for authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/projects', [
            'name'        => 'My Test Project',
            'description' => 'A project for testing',
        ]);

    $response
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'My Test Project');

    $this->assertDatabaseHas('projects', [
        'name'    => 'My Test Project',
        'user_id' => $user->id,
    ]);
});

it('prevents user B from accessing user A project', function () {
    $userA   = User::factory()->create();
    $userB   = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $userA->id]);

    $this->actingAs($userB)
        ->getJson("/api/projects/{$project->id}")
        ->assertStatus(403);
});

it('validates project name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/projects', ['description' => 'No name'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});