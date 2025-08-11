<?php

use App\Http\Controllers\Api\V1\MonitorController;
use App\Http\Controllers\Api\V1\MonitorLogController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ProjectController;

Route::group(['prefix' => 'v1'], function () {
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('monitors', MonitorController::class);
    Route::apiResource('monitors.logs', MonitorLogController::class);
    
});

// TODO: API TOKEN USAGE, the api should be protected also, right now only webapp is protected with laravel out of the box authorization
/*
Route::middleware('auth:sanctum')->group(['prefix' => 'v1'], function () {
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('monitors', MonitorController::class);
    Route::apiResource('monitors.logs', MonitorLogController::class);
});*/
