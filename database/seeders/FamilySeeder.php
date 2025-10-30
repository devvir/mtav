<?php

// Copilot - pending review

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
        $unitTypeNames = [
            'Studio' => 'Small single-room unit',
            '1 Bedroom' => 'One bedroom apartment',
            '2 Bedroom' => 'Two bedroom apartment',
            '3 Bedroom' => 'Three bedroom apartment',
            'Penthouse' => 'Luxury top-floor unit',
            'Duplex' => 'Two-story unit',
            'Loft' => 'Open-plan industrial-style unit',
        ];

        Project::pluck('id')->each(function (int $projectId) use ($unitTypeNames) {
            // Get existing unit types or create new ones if none exist
            $unitTypes = UnitType::where('project_id', $projectId)->get();

            if ($unitTypes->isEmpty()) {
                // Create 3-5 random unit types for this project
                $selectedTypes = collect($unitTypeNames)
                    ->random(random_int(3, 5));

                foreach ($selectedTypes as $name => $description) {
                    UnitType::firstOrCreate(
                        [
                            'project_id' => $projectId,
                            'name' => $name,
                        ],
                        [
                            'description' => $description,
                        ]
                    );
                }

                $unitTypes = UnitType::where('project_id', $projectId)->get();
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
