<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ProjectController;

Route::group(['prefix' => 'v1'], function () {
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('monitors', ProjectController::class);
});