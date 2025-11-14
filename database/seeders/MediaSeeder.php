<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Project;
use Illuminate\Database\Seeder;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        $projectIds = Project::pluck('id');

        if ($projectIds->isEmpty()) {
            $this->command->warn('No projects found. Run ProjectSeeder first.');
            return;
        }

        $projectIds->each(function ($projectId) {
            // Get all users who belong to this project (both admins and members)
            $project = Project::with(['admins', 'members'])->find($projectId);
            $projectUserIds = $project->admins->merge($project->members)->pluck('id');

            if ($projectUserIds->isEmpty()) {
                $this->command->warn("No users found for project {$projectId}. Skipping media creation.");
                return;
            }

            // Create media items for this project - only images and videos for now
            foreach (['image', 'video'] as $category) {
                Media::factory()
                    ->count(10) // 10 of each type = 20 total
                    ->category($category)
                    ->sequence(fn ($sequence) => [
                        'project_id' => $projectId,
                        'owner_id'   => $projectUserIds->random(),
                    ])
                    ->create();
            }
        });
    }
}
