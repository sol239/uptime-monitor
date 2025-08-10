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
        'status'
    ];

    protected function casts(): array
    {
        return [
            'periodicity' => 'integer',
            'status' => 'string',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

}
