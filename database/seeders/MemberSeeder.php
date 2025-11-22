<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Project;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        Project::with('families')->get()->each(
            fn (Project $project) => Member::factory()
                ->inProject($project)
                ->count($project->families->count() * 3)
                ->recycle($project->families)
                ->create()
        );
    }
}
