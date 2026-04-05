<?php

use App\Http\Controllers\Api\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;

Route::get('/health', function () {
    return response()->json([
        'status'    => 'ok',
        'timestamp' => now()->toISOString(),
    ]);
});

Route::group([],function(){
    Route::get('/user', function (Request $request) {
    return $request->user();
    });

    Route::apiResource('projects', ProjectController::class);

    Route::apiResource('projects.tasks', TaskController::class)
        ->shallow();
});



