<?php

use App\Http\Controllers\Api\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status'    => 'ok',
        'timestamp' => now()->toISOString(),
    ]);
});

Route::middleware('auth:sanctum')->group(function(){
    Route::get('/user', function (Request $request) {
    return $request->user();
    });

    Route::resource('projects',[ProjectController::class]);
});



