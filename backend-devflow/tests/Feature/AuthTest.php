<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a new user and returns tokens', function () {
    $response = $this->postJson('/api/auth/register', [
        'name'                  => 'Ali Khan',
        'email'                 => 'ali@test.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $response
        ->assertStatus(201)
        ->assertJsonPath('data.user.email', 'ali@test.com')
        ->assertJsonStructure([
            'data' => ['user', 'access_token', 'token_type'],
        ]);
});

it('logs in with valid credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonStructure(['data' => ['access_token']]);
});

it('returns 401 with invalid credentials', function () {
    User::factory()->create(['email' => 'test@test.com']);

    $this->postJson('/api/auth/login', [
        'email'    => 'test@test.com',
        'password' => 'wrongpassword',
    ])->assertStatus(401);
});

it('returns 422 when registering with existing email', function () {
    User::factory()->create(['email' => 'taken@test.com']);

    $this->postJson('/api/auth/register', [
        'name'                  => 'Another User',
        'email'                 => 'taken@test.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(422);
});