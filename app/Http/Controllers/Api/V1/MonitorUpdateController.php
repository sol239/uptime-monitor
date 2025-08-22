<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MonitorUpdate;
use Illuminate\Support\Facades\Log;

class MonitorUpdateController extends Controller
{
    // List all MonitorUpdates
    public function index()
    {
        Log::info("Fetching all MonitorUpdates");
        try {
            $updates = MonitorUpdate::all();
            Log::info("MonitorUpdates fetched", ['count' => $updates->count()]);
            return response()->json($updates);
        } catch (\Exception $e) {
            Log::error("Error fetching MonitorUpdates", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch MonitorUpdates'], 500);
        }
    }

    // Create a new MonitorUpdate
    public function store(Request $request)
    {
        Log::info("Creating MonitorUpdate", ['data' => $request->all()]);
        try {
            $data = $request->validate([
                'monitor_id' => 'required|integer|exists:monitors,id',
                'must_update' => 'required|boolean',
            ]);
            $monitorUpdate = MonitorUpdate::create($data);
            Log::info("MonitorUpdate created", ['id' => $monitorUpdate->id]);
            return response()->json($monitorUpdate, 201);
        } catch (\Exception $e) {
            Log::error("Error creating MonitorUpdate", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create MonitorUpdate'], 500);
        }
    }

    // Show a specific MonitorUpdate
    public function show(string $id)
    {
        Log::info("Fetching MonitorUpdate", ['id' => $id]);
        try {
            $monitorUpdate = MonitorUpdate::findOrFail($id);
            Log::info("MonitorUpdate fetched", ['id' => $monitorUpdate->id]);
            return response()->json($monitorUpdate);
        } catch (\Exception $e) {
            Log::error("Error fetching MonitorUpdate", ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch MonitorUpdate'], 500);
        }
    }

    // Update a MonitorUpdate
    public function update(Request $request, string $id)
    {
        Log::info("Updating MonitorUpdate", ['id' => $id, 'data' => $request->all()]);
        try {
            $monitorUpdate = MonitorUpdate::findOrFail($id);
            $data = $request->validate([
                'monitor_id' => 'sometimes|integer|exists:monitors,id',
                'must_update' => 'sometimes|boolean',
            ]);
            $monitorUpdate->update($data);
            Log::info("MonitorUpdate updated", ['id' => $monitorUpdate->id]);
            return response()->json($monitorUpdate);
        } catch (\Exception $e) {
            Log::error("Error updating MonitorUpdate", ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update MonitorUpdate'], 500);
        }
    }

    // Delete a MonitorUpdate
    public function destroy(string $id)
    {
        Log::info("Deleting MonitorUpdate", ['id' => $id]);
        try {
            $monitorUpdate = MonitorUpdate::findOrFail($id);
            $monitorUpdate->delete();
            Log::info("MonitorUpdate deleted", ['id' => $id]);
            return response()->json(['message' => 'Deleted']);
        } catch (\Exception $e) {
            Log::error("Error deleting MonitorUpdate", ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete MonitorUpdate'], 500);
        }
    }
}
