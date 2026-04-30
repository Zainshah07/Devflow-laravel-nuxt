<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TaskAssignmentController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskDependencyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectMemberController;

// ── Health check (public) ────────────────────────────────────────────
Route::get('/health', function () {
    try {
        \Illuminate\Support\Facades\DB::select('SELECT 1');
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        $dbStatus = 'error';
    }

    try {
        \Illuminate\Support\Facades\DB::connection('mysql_replica')->select('SELECT 1');
        $replicaStatus = 'connected';
    } catch (\Exception $e) {
        $replicaStatus = 'error';
    }

    try {
        \Illuminate\Support\Facades\Redis::ping();
        $redisStatus = 'connected';
    } catch (\Exception $e) {
        $redisStatus = 'error';
    }

    $allOk = $dbStatus === 'connected' && $redisStatus === 'connected';

    return response()->json([
        'status'    => $allOk ? 'ok' : 'degraded',
        'server'    => gethostname(),
        'timestamp' => now()->toISOString(),
        'checks'    => [
            'database'         => $dbStatus,
            'database_replica' => $replicaStatus,
            'redis'            => $redisStatus,
        ],
    ], $allOk ? 200 : 503);
});

// ── Auth routes (public) ─────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/refresh',  [AuthController::class, 'refresh']);
});

// ── Protected routes ─────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // Stats and leaderboard
    Route::get('/stats',                          [StatsController::class,      'index']);
    Route::get('/projects/{project}/leaderboard', [LeaderboardController::class, 'index']);

    // Task assignment
    Route::post('/tasks/{task}/assign',           [TaskAssignmentController::class, 'store']);
    Route::delete('/tasks/{task}/assign/{user}',  [TaskAssignmentController::class, 'destroy']);

    // Task dependencies
    Route::get('/tasks/{task}/dependencies',                         [TaskDependencyController::class, 'index']);
    Route::post('/tasks/{task}/dependencies',                        [TaskDependencyController::class, 'store']);
    Route::delete('/tasks/{task}/dependencies/{dependency}',         [TaskDependencyController::class, 'destroy']);
    Route::get('/projects/{projectId}/dependency-graph',             [TaskDependencyController::class, 'projectGraph']);

    Route::get('/projects/{project}/members',         [ProjectMemberController::class, 'index']);
    Route::post('/projects/{project}/members',        [ProjectMemberController::class, 'store']);
    Route::delete('/projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy']);
    // Projects and tasks
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('projects.tasks', TaskController::class)->shallow();
});