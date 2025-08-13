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
use App\Models\Monitor;
use Illuminate\Http\Request;

class MonitorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/monitors",
     *     summary="Get list of monitors",
     *     tags={"Monitors"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of monitors",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/Monitor")
     *         )
     *     )
     * )
     */
    public function index()
    {
        return Monitor::all();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/monitors",
     *     summary="Create a new monitor",
     *     tags={"Monitors"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/Monitor")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Monitor created",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Monitor")
     *     )
     * )
     */
    public function store(Request $request)
    {
        $monitor = Monitor::create($request->all());

        return response()->json($monitor, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/monitors/{monitor}",
     *     summary="Get monitor detail",
     *     tags={"Monitors"},
     *
     *     @OA\Parameter(
     *         name="monitor",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Monitor detail",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Monitor")
     *     )
     * )
     */
    public function show(Monitor $monitor)
    {
        return $monitor;
    }

    /**
     * @OA\Put(
     *     path="/api/v1/monitors/{monitor}",
     *     summary="Update monitor",
     *     tags={"Monitors"},
     *
     *     @OA\Parameter(
     *         name="monitor",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/Monitor")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Monitor updated",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Monitor")
     *     )
     * )
     */
    public function update(Request $request, Monitor $monitor)
    {
        $monitor->update($request->all());

        return $monitor;
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/monitors/{monitor}",
     *     summary="Delete monitor",
     *     tags={"Monitors"},
     *
     *     @OA\Parameter(
     *         name="monitor",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Monitor deleted"
     *     )
     * )
     */
    public function destroy(Monitor $monitor)
    {
        $monitor->delete();

        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/monitors/{monitor}/history",
     *     summary="Get monitor history",
     *     tags={"Monitors"},
     *
     *     @OA\Parameter(
     *         name="monitor",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="mode",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"status", "response", "latency"})
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Monitor history",
     *
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     )
     * )
     */
    public function history(Monitor $monitor, Request $request)
    {
        $mode = $request->get('mode', 'status');

        $logs = $monitor->logs()->orderBy('started_at', 'desc');

        if ($mode === 'status') {
            return $logs->select('id', 'started_at as timestamp', 'status')->get();
        } elseif ($mode === 'response') {
            return $logs->select('id', 'started_at as timestamp', 'response_time_ms as response')->get();
        } elseif ($mode === 'latency') {
            return $logs->select('id', 'started_at as timestamp', 'response_time_ms as latency')->get();
        }

        return $logs->get();
    }

    /**
     * @OA\Get(
     *     path="/api/v1/monitors/{monitor}/calendar-summary",
     *     summary="Get monitor calendar summary",
     *     tags={"Monitors"},
     *
     *     @OA\Parameter(
     *         name="monitor",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Calendar summary",
     *
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function calendarSummary(Monitor $monitor)
    {
        $logs = $monitor->logs()
            ->selectRaw('DATE(started_at) as date, COUNT(*) as total, SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $summary = [];
        foreach ($logs as $log) {
            $summary[$log->date] = [
                'total' => $log->total,
                'failed' => $log->failed,
            ];
        }

        return response()->json($summary);
    }
}
