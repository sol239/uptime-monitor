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
}