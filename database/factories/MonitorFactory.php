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
        $type = $this->faker->randomElement(['website', 'ping']);
        
        $data = [
            'project_id' => Project::factory(),
            'label' => $this->faker->words(2, true),
            'periodicity' => $this->faker->numberBetween(5, 300),
            'type' => $type,
            'badge_label' => $this->faker->word(),
            'status' => $this->faker->randomElement(['failed', 'succeeded']),
            'latest_status' => $this->faker->randomElement(['succeeded', 'failed']),
        ];

        // Add type-specific fields
        if ($type === 'ping') {
            $data['hostname'] = $this->faker->domainName();
            $data['port'] = $this->faker->numberBetween(80, 8080);
        } elseif ($type === 'website') {
            $data['url'] = $this->faker->url();
            $data['check_status'] = $this->faker->boolean();
            $data['keywords'] = $this->faker->words(3);
        }

        return $data;
    }
}
