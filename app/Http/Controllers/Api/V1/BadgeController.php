<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Monitor;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class BadgeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/badge/{monitorId}",
     *     summary="Get badge for a monitor",
     *     description="Returns an HTML badge representing the status of a monitor.",
     *     operationId="getBadge",
     *     tags={"Badge"},
     *
     *     @OA\Parameter(
     *         name="monitorId",
     *         in="path",
     *         required=true,
     *         description="ID of the monitor",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="HTML badge",
     *
     *         @OA\MediaType(
     *             mediaType="text/html",
     *             example="<img alt='Static Badge' src='https://img.shields.io/badge/badge-label-up-green'>"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Monitor not found"
     *     )
     * )
     */
    public function show($monitorId)
    {
        $monitor = Monitor::find($monitorId);

        if (! $monitor) {
            Log::error('Monitor not found', ['monitorId' => $monitorId]);
            $label = 'Monitor';
            $status = 'unknown';
            $color = 'gray';
        } else {
            $label = $monitor->label;
            $status = $monitor->status === 'succeeded' ? 'up' : 'down';
            $color = $status === 'up' ? 'green' : 'red';
            Log::info('Badge generated', [
                'monitorId' => $monitorId,
                'label' => $label,
                'status' => $status,
                'color' => $color,
            ]);
        }

        $encodedStatus = urlencode($status);

        $badgeContent = "badge label-{$encodedStatus}-{$color}";
        $badgeUrl = "https://img.shields.io/badge/{$badgeContent}";

        $html = '<img alt="Static Badge" src="'.$badgeUrl.'">';

        return response($html, 200)->header('Content-Type', 'text/html');
    }
}
