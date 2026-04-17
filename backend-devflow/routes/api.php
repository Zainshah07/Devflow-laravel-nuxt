<?php

use App\Http\Controllers\Api\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\AuthController;


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

    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('projects.tasks', TaskController::class)->shallow();
});



