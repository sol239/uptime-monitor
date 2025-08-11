<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *   schema="Monitor",
 *   type="object",
 *   title="Monitor",
 *   required={"project_id", "label", "periodicity", "type", "status"},
 *   @OA\Property(property="id", type="integer", format="int64", example=1),
 *   @OA\Property(property="project_id", type="integer", format="int64", example=1),
 *   @OA\Property(property="label", type="string", example="Ping Google"),
 *   @OA\Property(
 *       property="periodicity",
 *       type="integer",
 *       example=60,
 *       description="Interval in seconds (5-300)",
 *       minimum=5,
 *       maximum=300
 *   ),
 *   @OA\Property(property="monitor_type", type="string", example="ping"),
 *   @OA\Property(property="badge_label", type="string", example="Google"),
 *   @OA\Property(property="status", type="string", example="active"),
 *   @OA\Property(property="latest_status", type="string", example="succeeded"),
 *   @OA\Property(property="hostname", type="string", example="google.com"),
 *   @OA\Property(property="port", type="integer", example=80),
 *   @OA\Property(property="url", type="string", example="https://google.com"),
 *   @OA\Property(property="check_status", type="boolean", example=true),
 *   @OA\Property(property="keywords", type="array", @OA\Items(type="string"))
 * )
 */
class Monitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'label',
        'periodicity',   // TODO: The allowed range is between 5 and 300 seconds
        'monitor_type',
        'badge_label',
        'status',
    'latest_status', // Result of monitoring task: succeeded or failed
        // Ping monitor fields
        'hostname',      // Host name or IP address
        'port',          // Port to connect to
        // Website monitor fields
        'url',           // URL to connect to
    'check_status',  // If true, monitor is 'failed' when status is not in [200, 300)
    'keywords'       // List of keywords - monitor is 'failed' if any keyword is not in response
    ];

    protected function casts(): array
    {
        return [
            'periodicity' => 'integer',
            'port' => 'integer',
            'check_status' => 'boolean',
            'keywords' => 'array',
            'status' => 'string',
            'latest_status' => 'string',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function logs()
    {
        return $this->hasMany(MonitorLog::class);
    }
}
