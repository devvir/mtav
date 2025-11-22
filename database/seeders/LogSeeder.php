<?php

namespace Database\Seeders;

use App\Models\Log;
use App\Models\Project;
use Illuminate\Database\Seeder;

class LogSeeder extends Seeder
{
    public function run(): void
    {
        Project::with('users')->get()->each(
            fn (Project $project) => Log::factory()
                ->count(rand(100, 200))
                ->inProject($project)
                ->recycle($project->users)
                ->create()
        );
    }
}
