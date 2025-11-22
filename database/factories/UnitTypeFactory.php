<?php

namespace Database\Factories;

use App\Models\Project;
use Database\Factories\Concerns\InProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UnitType>
 */
class UnitTypeFactory extends Factory
{
    use InProject;

    public const TYPES = [
        ['Duplex', 'Two-story unit'],
        ['Penthouse', 'Top-floor unit'],
        ['Studio', 'Small single-room unit'],
        ['Loft', 'Open-plan industrial-style unit'],
        ['1 Bedroom', 'One bedroom apartment'],
        ['2 Bedroom', 'Two bedroom apartment'],
        ['3 Bedroom', 'Three bedroom apartment'],
    ];

    public function definition(): array
    {
        $type = fake()->randomElement(self::TYPES);

        return [
            'project_id'  => Project::factory(),
            'name'        => $type[0],
            'description' => $type[1],
        ];
    }
}
