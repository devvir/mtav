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

            // Create media items for this project - images only for now
            Media::factory()
                ->count(20) // 20 images per project
                ->category('image')
                ->sequence(fn ($sequence) => [
                    'project_id' => $projectId,
                    'owner_id'   => $projectUserIds->random(),
                ])
                ->create();
        });
    }
}
