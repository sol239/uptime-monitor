<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="MonitorLog",
 *   type="object",
 *   title="MonitorLog",
 *   required={"monitor_id", "started_at", "status"},
 *
 *   @OA\Property(property="id", type="integer", format="int64", example=1),
 *   @OA\Property(property="monitor_id", type="integer", format="int64", example=1),
 *   @OA\Property(property="started_at", type="string", format="date-time", example="2025-08-11T12:00:00Z"),
 *   @OA\Property(property="status", type="string", example="succeeded"),
 *   @OA\Property(property="response_time_ms", type="integer", example=123)
 * )
 */
class MonitorLog extends Model
{   
    use HasFactory;

    /*
    * The attributes that are mass assignable.
    */
    protected $fillable = [
        'monitor_id',
        'started_at',
        'status',
        'response_time_ms',
    ];

    /*
    * The attributes that should be cast to native types.
    */
    protected $casts = [
        'started_at' => 'datetime',
        'status' => 'string',
        'response_time_ms' => 'integer',
    ];

    /*
    * Get the monitor that owns the log.
    */
    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }
}
