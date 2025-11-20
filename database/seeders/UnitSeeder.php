<?php

// Copilot - pending review

namespace Database\Seeders;

use App\Models\Family;
use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Load all project IDs in a single query
        $projectIds = Project::pluck('id');

        if ($projectIds->isEmpty()) {
            $this->command->warn('No projects found. Run ProjectSeeder first.');
            return;
        }

        $projectIds->each(function ($projectId) {
            // Count families in this project
            $familyCount = Family::where('project_id', $projectId)->count();

            if ($familyCount === 0) {
                $this->command->warn("No families found for project {$projectId}. Skipping unit creation.");
                return;
            }

            // Create 2-5 unit types per project (if they don't exist)
            $unitTypes = UnitType::where('project_id', $projectId)->get();

            if ($unitTypes->isEmpty()) {
                $unitTypes = UnitType::factory()
                    ->count(fake()->numberBetween(2, 5))
                    ->create(['project_id' => $projectId]);
            }

            // Create as many units as families (real-world scenario)
            // Distribute units across unit types
            $unitsCreated = 0;
            $unitsPerType = intval(ceil($familyCount / $unitTypes->count()));

            foreach ($unitTypes as $index => $unitType) {
                $unitsToCreate = min($unitsPerType, $familyCount - $unitsCreated);

                if ($unitsToCreate <= 0) {
                    break;
                }

                Unit::factory()
                    ->count($unitsToCreate)
                    ->sequence(fn ($sequence) => [
                        'identifier'   => fake()->randomLetter() . ($unitsCreated + $sequence->index + 1),
                        'project_id'   => $projectId,
                        'unit_type_id' => $unitType->id,
                        'family_id'    => null,
                    ])
                    ->create();

                $unitsCreated += $unitsToCreate;
            }
        });
    }
}
