<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProjectController extends Controller
{
    // GET /api/projects
    public function index()
    {
        return Project::all();
    }

    // POST /api/projects
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = Project::create($validated);
        return response()->json($project, 201);
    }

    // GET /api/projects/{id}
    public function show($id)
    {
        return Project::findOrFail($id);
    }

    // PUT/PATCH /api/projects/{id}
    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->update($validated);
        return response()->json($project);
    }

    // DELETE /api/projects/{id}
    public function destroy($id)
    {
        Project::destroy($id);
        return response()->json(null, 204);
    }
}