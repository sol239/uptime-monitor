<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MonitorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'started_at',
        'status',
        'response_time_ms',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'status' => 'string',
        'response_time_ms' => 'integer',
    ];

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }
}
