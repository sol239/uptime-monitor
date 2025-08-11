<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MonitorLog;
use App\Models\Monitor;
use Illuminate\Http\Request;

class MonitorLogController extends Controller
{
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

    public function store(Request $request, $monitorId)
    {
        $data = $request->all();
        $data['monitor_id'] = $monitorId;
        
        $monitorLog = MonitorLog::create($data);
        return response()->json($monitorLog, 201);
    }

    public function show(MonitorLog $monitorLog)
    {
        return $monitorLog;
    }

    public function update(Request $request, MonitorLog $monitorLog)
    {
        $monitorLog->update($request->all());
        return $monitorLog;
    }

    public function destroy(MonitorLog $monitorLog)
    {
        $monitorLog->delete();
        return response()->json(null, 204);
    }
}