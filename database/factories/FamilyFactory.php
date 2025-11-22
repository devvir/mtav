<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\UnitType;
use Database\Factories\Concerns\InProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Family>
 */
class FamilyFactory extends Factory
{
    use InProject;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id'   => Project::factory(),
            'name'         => fake()->lastName() . ' ' . fake()->lastName(),
            'unit_type_id' => $this->inSameProject(UnitType::class),
        ];
    }
}
