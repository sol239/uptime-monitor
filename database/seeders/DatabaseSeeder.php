<?php

namespace Database\Seeders;

use App\Models\Monitor;
use App\Models\Project;
use App\Models\User;
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
            'email' => 'test1@example.com',
        ]);

        User::factory()->create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
        ]);

        $project1 = Project::factory()->create([
            'label' => 'E-commerce Platform',
            'description' => 'Main e-commerce website monitoring.',
            'tags' => ['production', 'ecommerce', 'critical'],
            'user_id' => 1,
        ]);

        // TO TEST PAGINATION
        for ($i = 1; $i <= 20; $i++) {
            Project::factory()->create([
                'label' => 'E-commerce Platform '.$i,
                'description' => 'Main e-commerce website monitoring.',
                'tags' => ['production', 'ecommerce', 'critical'],
                'user_id' => 2,
            ]);
        }

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
            'monitor_type' => 'ping',
            'label' => 'Database Server Health',
            'hostname' => 'httpbin.org',
            'port' => 80,
            'latest_status' => 'succeeded',
            'url' => null,
            'keywords' => null,
            'check_status' => null,
        ]);

        for ($i = 1; $i <= 20; $i++) {
            Monitor::factory()->create([
                'project_id' => $project2->id,
                'monitor_type' => 'ping',
                'label' => 'Server Health Check '.$i,
                'hostname' => 'example.com',
                'port' => 80,
                'latest_status' => 'succeeded',
                'url' => null,
                'keywords' => null,
                'check_status' => null,
            ]);
        }

        Monitor::factory()->create([
            'project_id' => $project1->id,
            'monitor_type' => 'website',
            'label' => 'Task Assignment Availability',
            'url' => 'https://webik.ms.mff.cuni.cz/nswi153/seminar-project/',
            'check_status' => true,
            'keywords' => ['monitor', 'project'],
            'latest_status' => 'succeeded',
        ]);

        // Create monitors for project 2
        Monitor::factory()->create([
            'project_id' => $project2->id,
            'monitor_type' => 'ping',
            'label' => 'Redis Cache Server',
            'hostname' => 'httpbin.org',
            'port' => 443,
            'latest_status' => 'succeeded',
            'url' => null,
            'keywords' => null,
            'check_status' => null,
        ]);

        Monitor::factory()->create([
            'project_id' => $project2->id,
            'monitor_type' => 'website',
            'label' => 'SIS UK',
            'url' => 'https://is.cuni.cz/studium/index.php',
            'check_status' => true,
            'keywords' => ['Předměty', 'Login'],
            'latest_status' => 'succeeded',
        ]);

        Monitor::factory()->create([
            'project_id' => $project2->id,
            'monitor_type' => 'website',
            'label' => 'Seznam.cz',
            'url' => 'https://www.seznam.cz/',
            'check_status' => true,
            'keywords' => [],
            'latest_status' => 'succeeded',
        ]);

        // Create 1 monitor for project 3
        Monitor::factory()->create([
            'project_id' => $project3->id,
            'monitor_type' => 'ping',
            'label' => 'Json Placeholder',
            'hostname' => 'jsonplaceholder.typicode.com',
            'port' => 443,
            'latest_status' => 'failed',
            'url' => null,
            'keywords' => null,
            'check_status' => null,
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
            'monitor_type' => 'website',
            'label' => 'LibGen Availability',
            'url' => 'https://www.libgen.is/',
            'check_status' => true,
            'keywords' => [],
            'latest_status' => 'succeeded',
            'periodicity' => 300,
            'badge_label' => 'LibGen',
        ]);

        Monitor::factory()->create([
            'project_id' => $project4->id,
            'monitor_type' => 'website',
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
            'monitor_type' => 'website',
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
