<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'label' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'tags' => $this->faker->randomElements(['frontend', 'backend', 'api', 'devops', 'test', 'prod'], rand(1, 3)),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
