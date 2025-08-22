<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="MonitorUpdate",
 *   type="object",
 *   title="MonitorUpdate",
 *   required={"monitor_id", "must_update"},
 *
 *   @OA\Property(property="id", type="integer", format="int64", example=1),
 *   @OA\Property(property="monitor_id", type="integer", format="int64", example=1),
 *   @OA\Property(property="must_update", type="boolean", example=true)
 * )
 */
class MonitorUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'must_update',
    ];

    protected $casts = [
        'must_update' => 'boolean',
    ];

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }
}
