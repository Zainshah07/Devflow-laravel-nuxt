<?php

use App\Http\Controllers\Api\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskAssignmentController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\LeaderboardController;



Route::get('/health', function () {
    return response()->json([
        'status'    => 'ok',
        'timestamp' => now()->toISOString(),
    ]);
});

// ── Auth routes (public) ────────────────────────────────────────────
// DSA — Stack: these routes sit outside the auth middleware stack.
// Requests reach the controller directly without passing through
// the Sanctum token verification layer.
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/refresh',  [AuthController::class, 'refresh']);
});

// ── Protected routes ────────────────────────────────────────────────
// DSA — Stack: auth:sanctum sits at the top of the middleware stack
// for all routes in this group. Every request must pass through it
// before reaching any controller. Invalid tokens short-circuit here.
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

     // Stats endpoint
    Route::get('/stats', [StatsController::class, 'index']);

    Route::get('/projects/{project}/leaderboard', [LeaderboardController::class, 'index']);

    // Task assignment
    Route::post('/tasks/{task}/assign',            [TaskAssignmentController::class, 'store']);
    Route::delete('/tasks/{task}/assign/{user}',   [TaskAssignmentController::class, 'destroy']);

    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('projects.tasks', TaskController::class)->shallow();
});



