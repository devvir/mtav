<?php

// Copilot - pending review

namespace Database\Seeders;

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
            // Create 1-3 unit types per project (if they don't exist)
            $unitTypes = UnitType::where('project_id', $projectId)->get();

            if ($unitTypes->isEmpty()) {
                $unitTypes = UnitType::factory()
                    ->count(fake()->numberBetween(1, 3))
                    ->create(['project_id' => $projectId]);
            }

            $unitTypeIds = $unitTypes->pluck('id');

            // Create 5-20 units for this project
            $unitsCount = fake()->numberBetween(5, 20);

            Unit::factory()
                ->count($unitsCount)
                ->sequence(fn ($sequence) => [
                    'number' => (string) ($sequence->index + 1),
                    'project_id' => $projectId,
                    'unit_type_id' => $unitTypeIds->random(),
                    'family_id' => null,
                ])
                ->create();
        });
    }
}
