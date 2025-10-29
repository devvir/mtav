<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\Project;
use App\Models\UnitType;
use Illuminate\Database\Seeder;
use Illuminate\Database\UniqueConstraintViolationException;

class FamilySeeder extends Seeder
{
    public function run(): void
    {
        Project::pluck('id')->each(function (int $projectId) {
            // Get existing unit types or create new ones if none exist
            $unitTypes = UnitType::where('project_id', $projectId)->get();

            if ($unitTypes->isEmpty()) {
                $unitTypes = UnitType::factory()->count(3)->create(['project_id' => $projectId]);
            }

            $familiesCreated = 0;
            $targetCount = random_int(10, 50);

            while ($familiesCreated < $targetCount) {
                try {
                    Family::factory()->withMembers()->create([
                        'project_id' => $projectId,
                        'unit_type_id' => $unitTypes->random()->id,
                    ]);
                    $familiesCreated++;
                } catch (UniqueConstraintViolationException $e) {
                    continue;
                }
            }
        });
    }
}
