<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Monitor;
use App\Models\MonitorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MonitorLogController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/monitors/{monitorId}/logs",
     *     summary="Get monitor logs",
     *     tags={"MonitorLogs"},
     *
     *     @OA\Parameter(
     *         name="monitorId",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="string", format="date")
     *     ),
     *
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="string", format="date")
     *     ),
     *
     *     @OA\Parameter(
     *         name="response_time_only",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="boolean")
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of monitor logs",
     *
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function index($monitorId, Request $request)
    {
        Log::info("Fetching monitor logs", [
            'monitor_id' => $monitorId,
            'params' => $request->all()
        ]);

        try {
            $query = MonitorLog::where('monitor_id', $monitorId);

            // Filter by status if provided
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            // Filter by date range if provided
            if ($request->has('start_date') && $request->start_date !== '') {
                $query->whereDate('started_at', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date !== '') {
                $query->whereDate('started_at', '<=', $request->end_date);
            }

            // Handle response_time_only parameter for graph data
            if ($request->has('response_time_only') && $request->response_time_only) {
                $query->select('started_at', 'response_time_ms');
            }

            // Order by started_at descending
            $query->orderBy('started_at', 'desc');

            // Handle pagination
            if ($request->has('page') && $request->has('per_page')) {
                $page = (int) $request->page;
                $perPage = min((int) $request->per_page, 100); // Max 100 per page

                $total = $query->count();
                $logs = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

                Log::info("Paginated monitor logs fetched", [
                    'monitor_id' => $monitorId,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total
                ]);

                return response()->json([
                    'logs' => $logs,
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                ]);
            }

            Log::info("Monitor logs fetched", [
                'monitor_id' => $monitorId,
                'count' => $query->count()
            ]);

            return response()->json([
                'logs' => $query->get(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching monitor logs", [
                'monitor_id' => $monitorId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to fetch logs'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/monitors/{monitorId}/logs",
     *     summary="Create a monitor log",
     *     tags={"MonitorLogs"},
     *
     *     @OA\Parameter(
     *         name="monitorId",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/MonitorLog")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Monitor log created",
     *
     *         @OA\JsonContent(ref="#/components/schemas/MonitorLog")
     *     )
     * )
     */
    public function store(Request $request, $monitorId)
    {
        Log::info("Creating monitor log", [
            'monitor_id' => $monitorId,
            'data' => $request->all()
        ]);

        try {
            $data = $request->all();
            $data['monitor_id'] = $monitorId;

            $monitorLog = MonitorLog::create($data);

            Log::info("Monitor log created", [
                'monitor_log_id' => $monitorLog->id
            ]);

            return response()->json($monitorLog, 201);
        } catch (\Exception $e) {
            Log::error("Error creating monitor log", [
                'monitor_id' => $monitorId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to create log'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/logs/{monitorLog}",
     *     summary="Get monitor log detail",
     *     tags={"MonitorLogs"},
     *
     *     @OA\Parameter(
     *         name="monitorLog",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Monitor log detail",
     *
     *         @OA\JsonContent(ref="#/components/schemas/MonitorLog")
     *     )
     * )
     */
    public function show(MonitorLog $monitorLog)
    {
        Log::info("Fetching monitor log detail", [
            'monitor_log_id' => $monitorLog->id
        ]);
        return $monitorLog;
    }

    /**
     * @OA\Put(
     *     path="/api/v1/logs/{monitorLog}",
     *     summary="Update monitor log",
     *     tags={"MonitorLogs"},
     *
     *     @OA\Parameter(
     *         name="monitorLog",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/MonitorLog")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Monitor log updated",
     *
     *         @OA\JsonContent(ref="#/components/schemas/MonitorLog")
     *     )
     * )
     */
    public function update(Request $request, MonitorLog $monitorLog)
    {
        Log::info("Updating monitor log", [
            'monitor_log_id' => $monitorLog->id,
            'data' => $request->all()
        ]);

        try {
            $monitorLog->update($request->all());

            Log::info("Monitor log updated", [
                'monitor_log_id' => $monitorLog->id
            ]);

            return $monitorLog;
        } catch (\Exception $e) {
            Log::error("Error updating monitor log", [
                'monitor_log_id' => $monitorLog->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to update log'], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/logs/{monitorLog}",
     *     summary="Delete monitor log",
     *     tags={"MonitorLogs"},
     *
     *     @OA\Parameter(
     *         name="monitorLog",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Monitor log deleted"
     *     )
     * )
     */
    public function destroy(MonitorLog $monitorLog)
    {
        Log::info("Deleting monitor log", [
            'monitor_log_id' => $monitorLog->id
        ]);

        try {
            $monitorLog->delete();

            Log::info("Monitor log deleted", [
                'monitor_log_id' => $monitorLog->id
            ]);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error("Error deleting monitor log", [
                'monitor_log_id' => $monitorLog->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to delete log'], 500);
        }
    }
}
