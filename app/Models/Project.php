<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{

    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'label',
        'description',
        'tags',
        'user_id'
    ];

    /*
    protected $casts = [
        'tags' => 'array',
    ];*/

    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function monitors()
    {
        return $this->hasMany(Monitor::class);
    }

}
