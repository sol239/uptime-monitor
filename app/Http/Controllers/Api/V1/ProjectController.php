<?php

/**
 * @OA\Info(
 *     title="Monitor API",
 *     version="1.0.0",
 *     description="API documentation for Monitor Vue project"
 * )
 */

namespace App\Http\Controllers\Api\V1;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/projects",
     *     summary="Get list of projects",
     *     tags={"Projects"},
     *     @OA\Response(
     *         response=200,
     *         description="List of projects",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Project"))
     *     )
     * )
     */
    // GET /api/projects
    public function index()
    {
        return Project::all();
    }

    /**
     * @OA\Post(
     *     path="/api/projects",
     *     summary="Create a new project",
     *     tags={"Projects"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", maxLength=255),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string", maxLength=255))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Project created",
     *         @OA\JsonContent(ref="#/components/schemas/Project")
     *     )
     * )
     */
    // POST /api/projects
    public function store(Request $request)
    {
        // T
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'user_id' => 'required|integer', // Ensure user_id is present in request
        ]);

        // Log validated array in the requested format
        Log::info($validated);

        $project = Project::create($validated);
        return response()->json($project, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/projects/{id}",
     *     summary="Get project detail",
     *     tags={"Projects"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Project detail",
     *         @OA\JsonContent(ref="#/components/schemas/Project")
     *     )
     * )
     */
    // GET /api/projects/{id}
    public function show($id)
    {
        return Project::findOrFail($id);
    }

    /**
     * @OA\Put(
     *     path="/api/projects/{id}",
     *     summary="Update project",
     *     tags={"Projects"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", maxLength=255),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string", maxLength=255))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Project updated",
     *         @OA\JsonContent(ref="#/components/schemas/Project")
     *     )
     * )
     * @OA\Patch(
     *     path="/api/projects/{id}",
     *     summary="Partially update project",
     *     tags={"Projects"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="label", type="string", maxLength=255),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string", maxLength=255))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Project updated",
     *         @OA\JsonContent(ref="#/components/schemas/Project")
     *     )
     * )
     */
    // PUT/PATCH /api/projects/{id}
    public function update(Request $request, $id)
    {
    
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
        ]);

        Log::info("Project [ID:" . $project->id . "]" . " updated.", $validated);
        $project->update($validated);
        return response()->json($project);
    }

    /**
     * @OA\Delete(
     *     path="/api/projects/{id}",
     *     summary="Delete project",
     *     tags={"Projects"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Project deleted"
     *     )
     * )
     */
    // DELETE /api/projects/{id}
    public function destroy($id)
    {
        Project::destroy($id);
        return response()->json(null, 204);
    }
}
    