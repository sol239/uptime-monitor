<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Monitor;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{   
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User 1',
            'email' => 'test1@example.com'
        ]);

        User::factory()->create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com'
        ]);

        $project1 = Project::factory()->create([
            'label' => 'Test Project',
            'description' => 'This is a test project.',
            'tags' => ['test', 'project'],
            'user_id' => 1,
        ]);

        $project2 = Project::factory()->create([
            'label' => 'Test Project',
            'description' => 'This is a test project.',
            'tags' => ['test', 'project'],
            'user_id' => 2,
        ]);

        $project3 = Project::factory()->create([
            'label' => 'Extra Project',
            'description' => 'This is another project for user 2.',
            'tags' => ['extra', 'user2'],
            'user_id' => 2,
        ]);

        // Create 2 monitors for project 1
        Monitor::factory()->count(2)->create([
            'project_id' => $project1->id,
        ]);

        // Create 3 monitors for project 2
        Monitor::factory()->count(3)->create([
            'project_id' => $project2->id,
        ]);

        // Create 1 monitor for project 3
        Monitor::factory()->create([
            'project_id' => $project3->id,
        ]);
    }
}
