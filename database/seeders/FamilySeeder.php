<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\Project;
use Illuminate\Database\Seeder;

class FamilySeeder extends Seeder
{
    public function run(): void
    {
        Project::pluck('id')->each(function (int $projectId) {
            Family::factory()->count(random_int(10, 50))->withMembers()->create([
                'project_id' => $projectId,
            ]);
        });
    }
}
