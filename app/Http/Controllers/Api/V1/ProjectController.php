<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/*
    ProjectController handles the CRUD operations for projects.
*/
class ProjectController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/projects",
     *     summary="Get list of projects",
     *     tags={"Projects"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of projects",
     *
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Project"))
     *     )
     * )
     */
    // GET /api/projects
    public function index()
    {
        Log::info('ProjectController@index called');
        try {
            $result = Project::all();
            Log::info('ProjectController@index success', ['count' => count($result)]);

            return $result;
        } catch (\Exception $e) {
            Log::error('ProjectController@index error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * @OA\Post(
     *     path="/api/projects",
     *     summary="Create a new project",
     *     tags={"Projects"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"label"},
     *
     *             @OA\Property(property="label", type="string", maxLength=255),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string", maxLength=255))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Project created",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Project")
     *     )
     * )
     */
    // POST /api/projects
    public function store(Request $request)
    {
        Log::info('ProjectController@store called', $request->all());
        try {
            $validated = $request->validate([
                'label' => 'required|string|max:255',
                'description' => 'nullable|string',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:255',
                'user_id' => 'required|integer',
            ]);
            Log::info('ProjectController@store validated', $validated);
            $project = Project::create($validated);
            Log::info('ProjectController@store created', ['id' => $project->id]);

            return response()->json($project, 201);
        } catch (\Exception $e) {
            Log::error('ProjectController@store error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * @OA\Get(
     *     path="/api/projects/{id}",
     *     summary="Get project detail",
     *     tags={"Projects"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Project detail",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Project")
     *     )
     * )
     */
    // GET /api/projects/{id}
    public function show($id)
    {
        Log::info('ProjectController@show called', ['id' => $id]);
        try {
            $project = Project::findOrFail($id);
            Log::info('ProjectController@show success', ['id' => $id]);

            return $project;
        } catch (\Exception $e) {
            Log::error('ProjectController@show error', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * @OA\Put(
     *     path="/api/projects/{id}",
     *     summary="Update project",
     *     tags={"Projects"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"label"},
     *
     *             @OA\Property(property="label", type="string", maxLength=255),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string", maxLength=255))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Project updated",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Project")
     *     )
     * )
     *
     * @OA\Patch(
     *     path="/api/projects/{id}",
     *     summary="Partially update project",
     *     tags={"Projects"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="label", type="string", maxLength=255),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string", maxLength=255))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Project updated",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Project")
     *     )
     * )
     */
    // PUT/PATCH /api/projects/{id}
    public function update(Request $request, $id)
    {
        Log::info('ProjectController@update called', ['id' => $id, 'data' => $request->all()]);
        try {
            $project = Project::findOrFail($id);
            $validated = $request->validate([
                'label' => 'required|string|max:255',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:255',
            ]);
            Log::info('ProjectController@update validated', $validated);
            $project->update($validated);
            Log::info('ProjectController@update success', ['id' => $project->id]);

            return response()->json($project);
        } catch (\Exception $e) {
            Log::error('ProjectController@update error', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/projects/{id}",
     *     summary="Delete project",
     *     tags={"Projects"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Project deleted"
     *     )
     * )
     */
    // DELETE /api/projects/{id}
    public function destroy($id)
    {
        Log::info('ProjectController@destroy called', ['id' => $id]);
        try {
            Project::destroy($id);
            Log::info('ProjectController@destroy success', ['id' => $id]);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('ProjectController@destroy error', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
