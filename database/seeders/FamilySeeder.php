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
            // Create families with retry logic for duplicate names
            $familiesCreated = 0;
            $targetCount = random_int(10, 50);

            while ($familiesCreated < $targetCount) {
                try {
                    Family::factory()->withMembers()->create([
                        'project_id' => $projectId,
                    ]);
                    $familiesCreated++;
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    // Skip duplicates and continue
                    continue;
                }
            }
        });
    }
}
