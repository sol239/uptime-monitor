<?php

use App\Http\Controllers\Api\V1\MonitorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ProjectController;

Route::group(['prefix' => 'v1'], function () {
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('monitors', MonitorController::class);
});