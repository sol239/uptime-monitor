<?php

/**
 * @OA\Info(
 *     title="Monitor API",
 *     version="1.0.0",
 *     description="API documentation for Monitor Vue project"
 * )
 */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MonitorLog;
use App\Models\Monitor;
use Illuminate\Http\Request;

class MonitorLogController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/monitors/{monitorId}/logs",
     *     summary="Get monitor logs",
     *     tags={"MonitorLogs"},
     *     @OA\Parameter(
     *         name="monitorId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="response_time_only",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of monitor logs",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function index($monitorId, Request $request)
    {
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

            return response()->json([
                'logs' => $logs,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage
            ]);
        }

        return response()->json([
            'logs' => $query->get()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/monitors/{monitorId}/logs",
     *     summary="Create a monitor log",
     *     tags={"MonitorLogs"},
     *     @OA\Parameter(
     *         name="monitorId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/MonitorLog")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Monitor log created",
     *         @OA\JsonContent(ref="#/components/schemas/MonitorLog")
     *     )
     * )
     */
    public function store(Request $request, $monitorId)
    {
        $data = $request->all();
        $data['monitor_id'] = $monitorId;

        $monitorLog = MonitorLog::create($data);
        return response()->json($monitorLog, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/logs/{monitorLog}",
     *     summary="Get monitor log detail",
     *     tags={"MonitorLogs"},
     *     @OA\Parameter(
     *         name="monitorLog",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Monitor log detail",
     *         @OA\JsonContent(ref="#/components/schemas/MonitorLog")
     *     )
     * )
     */
    public function show(MonitorLog $monitorLog)
    {
        return $monitorLog;
    }

    /**
     * @OA\Put(
     *     path="/api/v1/logs/{monitorLog}",
     *     summary="Update monitor log",
     *     tags={"MonitorLogs"},
     *     @OA\Parameter(
     *         name="monitorLog",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/MonitorLog")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Monitor log updated",
     *         @OA\JsonContent(ref="#/components/schemas/MonitorLog")
     *     )
     * )
     */
    public function update(Request $request, MonitorLog $monitorLog)
    {
        $monitorLog->update($request->all());
        return $monitorLog;
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/logs/{monitorLog}",
     *     summary="Delete monitor log",
     *     tags={"MonitorLogs"},
     *     @OA\Parameter(
     *         name="monitorLog",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Monitor log deleted"
     *     )
     * )
     */
    public function destroy(MonitorLog $monitorLog)
    {
        $monitorLog->delete();
        return response()->json(null, 204);
    }
}
