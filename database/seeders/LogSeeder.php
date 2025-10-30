<?php

// Copilot - pending review

namespace Database\Seeders;

use App\Models\Log;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class LogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projectIds = Project::pluck('id');

        if ($projectIds->isEmpty()) {
            $this->command->warn('No projects found. Please run ProjectSeeder first.');

            return;
        }

        $logEvents = [
            'User logged in',
            'User logged out',
            'Project updated',
            'Unit created',
            'Unit updated',
            'Unit deleted',
            'Family created',
            'Family updated',
            'Family deleted',
            'Member added to family',
            'Member removed from family',
            'Payment received',
            'Payment updated',
            'Document uploaded',
            'Document deleted',
            'Settings changed',
            'Report generated',
            'Email sent',
            'Notification created',
            'Task completed',
        ];

        // Get all user IDs once (agnostic - doesn't care about member/admin distinction)
        $allUsers = User::all();

        if ($allUsers->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');

            return;
        }

        $projectIds->each(function ($projectId) use ($logEvents, $allUsers) {
            // Create 100-200 logs per project
            $logCount = rand(100, 200);

            for ($i = 0; $i < $logCount; $i++) {
                Log::create([
                    'event' => $logEvents[array_rand($logEvents)],
                    'user_id' => rand(0, 10) > 0 ? $allUsers->random()->id : null, // 90% have user, 10% are system logs
                    'project_id' => $projectId,
                    'created_at' => now()->subDays(rand(0, 365))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                ]);
            }
        });
    }
}
