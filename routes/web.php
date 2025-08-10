<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('projects', function () {
    $projects = Project::where('user_id', Auth::id())->get();

    return Inertia::render('Projects', [
        'projects' => $projects
    ]);
})->middleware(['auth', 'verified'])->name('projects');

Route::get('projects/{project_id}', function ($project_id) {
    $project = Project::findOrFail($project_id);
    $monitors = $project->monitors;

    // Add debugging
    Log::info('Project data:', $project->toArray());
    
    return Inertia::render('ProjectDetails', [
        'project' => $project,
        'monitors' => $monitors
    ]);
})->middleware(['auth', 'verified'])->name('project.show');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
