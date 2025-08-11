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
            'label' => 'E-commerce Platform',
            'description' => 'Main e-commerce website monitoring.',
            'tags' => ['production', 'ecommerce', 'critical'],
            'user_id' => 1,
        ]);

        $project2 = Project::factory()->create([
            'label' => 'API Gateway',
            'description' => 'REST API services monitoring.',
            'tags' => ['api', 'microservices', 'production'],
            'user_id' => 2,
        ]);

        $project3 = Project::factory()->create([
            'label' => 'Development Environment',
            'description' => 'Development and staging servers monitoring.',
            'tags' => ['development', 'staging', 'testing'],
            'user_id' => 2,
        ]);

        // Create specific monitors for project 1
        Monitor::factory()->create([
            'project_id' => $project1->id,
            'type' => 'ping',
            'label' => 'Database Server Health',
            'hostname' => 'db.ecommerce.com',
            'port' => 5432,
            'latest_status' => 'succeeded',
        ]);

        Monitor::factory()->create([
            'project_id' => $project1->id,
            'type' => 'website',
            'label' => 'Homepage Availability',
            'url' => 'https://shop.example.com',
            'check_status' => true,
            'keywords' => ['Welcome', 'Shop', 'Products'],
            'latest_status' => 'succeeded',
        ]);

        // Create monitors for project 2
        Monitor::factory()->create([
            'project_id' => $project2->id,
            'type' => 'ping',
            'label' => 'Redis Cache Server',
            'hostname' => 'redis.api.com',
            'port' => 6379,
            'latest_status' => 'succeeded',
        ]);

        Monitor::factory()->create([
            'project_id' => $project2->id,
            'type' => 'website',
            'label' => 'API Health Check',
            'url' => 'https://api.example.com/health',
            'check_status' => true,
            'keywords' => ['healthy', 'status'],
            'latest_status' => 'succeeded',
        ]);

        Monitor::factory()->create([
            'project_id' => $project2->id,
            'type' => 'website',
            'label' => 'User Service API',
            'url' => 'https://api.example.com/users/ping',
            'check_status' => true,
            'keywords' => ['pong'],
            'latest_status' => 'succeeded',
        ]);

        // Create 1 monitor for project 3
        Monitor::factory()->create([
            'project_id' => $project3->id,
            'type' => 'ping',
            'label' => 'Development Server',
            'hostname' => 'dev-server.local',
            'port' => 8080,
            'latest_status' => 'failed',
        ]);

        $project4 = Project::factory()->create([
            'label' => 'Shadow Libraries',
            'description' => 'Monitoring availability of digital library resources.',
            'tags' => ['libraries', 'monitoring', 'research'],
            'user_id' => 1,
        ]);

        // Create monitors for Shadow Libraries project
        Monitor::factory()->create([
            'project_id' => $project4->id,
            'type' => 'website',
            'label' => 'LibGen Availability',
            'url' => 'https://www.libgen.is/',
            'check_status' => true,
            'keywords' => ['Library', 'Genesis'],
            'latest_status' => 'succeeded',
            'periodicity' => 300,
            'badge_label' => 'LibGen',
        ]);

        Monitor::factory()->create([
            'project_id' => $project4->id,
            'type' => 'website',
            'label' => 'Anna\'s Archive Status',
            'url' => 'https://annas-archive.org/',
            'check_status' => true,
            'keywords' => ['Anna', 'library', 'books'],
            'latest_status' => 'succeeded',
            'periodicity' => 300,
            'badge_label' => 'AnnaArchive',
        ]);

        Monitor::factory()->create([
            'project_id' => $project4->id,
            'type' => 'website',
            'label' => 'Z-Library Health',
            'url' => 'https://z-library.sk/',
            'check_status' => true,
            'keywords' => ['Search', 'knowledge'],
            'latest_status' => 'succeeded',
            'periodicity' => 300,
            'badge_label' => 'ZLibrary',
        ]);
    }
}
