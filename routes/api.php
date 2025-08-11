<?php

use App\Http\Controllers\Api\V1\MonitorController;
use App\Http\Controllers\Api\V1\MonitorLogController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ProjectController;

Route::group(['prefix' => 'v1'], function () {
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('monitors', MonitorController::class);
    Route::apiResource('monitors.logs', MonitorLogController::class);
    
    // Additional monitor endpoints
    Route::get('monitors/{monitor}/history', [MonitorController::class, 'history']);
    Route::get('monitors/{monitor}/calendar-summary', [MonitorController::class, 'calendarSummary']);
});