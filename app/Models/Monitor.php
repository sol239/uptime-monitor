<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Monitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'label',
        'periodicity',   // TODO: The allowed range is between 5 and 300 seconds
        'type',
        'badge_label',
        'status',
        'latest_status', // Result of monitoring task: succeeded or failed
        // Ping monitor fields
        'hostname',      // Host name or IP address
        'port',          // Port to connect to
        // Website monitor fields
        'url',           // URL to connect to
        'check_status',  // If true, monitor fails when status is not in [200, 300)
        'keywords'       // List of keywords - monitor fails if any keyword is not in response
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
