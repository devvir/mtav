<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        Project::with('unitTypes')->get()->each(
            fn (Project $project) => Unit::factory()
                ->count(rand(20, 50))
                ->inProject($project)
                ->recycle($project->unitTypes)
                ->create()
        );
    }
}
