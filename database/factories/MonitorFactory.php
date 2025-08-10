<?php

namespace Database\Factories;

use App\Models\Monitor;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonitorFactory extends Factory
{
    protected $model = Monitor::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'label' => $this->faker->words(2, true),
            'periodicity' => $this->faker->numberBetween(5, 300),
            'type' => $this->faker->randomElement(['website', 'ping']),
            'badge_label' => $this->faker->word(),
            'status' => $this->faker->randomElement(['failed', 'succeeded']),
        ];
    }
}
