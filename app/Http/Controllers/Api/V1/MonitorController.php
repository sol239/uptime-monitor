<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Monitor;
use Illuminate\Http\Request;

class MonitorController extends Controller
{
    public function index()
    {
        return Monitor::all();
    }

    public function store(Request $request)
    {
        $monitor = Monitor::create($request->all());
        return response()->json($monitor, 201);
    }

    public function show(Monitor $monitor)
    {
        return $monitor;
    }

    public function update(Request $request, Monitor $monitor)
    {
        $monitor->update($request->all());
        return $monitor;
    }

    public function destroy(Monitor $monitor)
    {
        $monitor->delete();
        return response()->json(null, 204);
    }

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
                'failed' => $log->failed
            ];
        }
        
        return response()->json($summary);
    }
}