<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\UnitType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Family>
 */
class FamilyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'unit_type_id' => UnitType::factory(),
            'project_id'   => fn (array $attributes) => UnitType::find($attributes['unit_type_id'])->project_id,
            'name'         => fake()->unique()->lastName() . ' ' . fake()->lastName(),
        ];
    }
}
