<?php

namespace Database\Seeders;

use App\Enums\MediaCategory;
use App\Models\Media;
use App\Models\Project;
use Illuminate\Database\Seeder;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        Project::with('users')->get()->each(
            fn (Project $project) => Media::factory()
                ->count(rand(5, 40))
                ->inProject($project)
                ->recycle($project->users)
                ->category(MediaCategory::IMAGE)
                ->create()
        );
    }
}
