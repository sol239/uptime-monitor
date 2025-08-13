<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="Project",
 *   type="object",
 *   title="Project",
 *   required={"label", "user_id"},
 *
 *   @OA\Property(property="id", type="integer", format="int64", example=1),
 *   @OA\Property(property="label", type="string", example="My Project"),
 *   @OA\Property(property="description", type="string", example="Project description"),
 *   @OA\Property(property="tags", type="array", @OA\Items(type="string")),
 *   @OA\Property(property="user_id", type="integer", format="int64", example=1)
 * )
 */
class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'label',
        'description',
        'tags',
        'user_id',
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
