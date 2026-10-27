<?php

namespace Database\Factories;

use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'unit_type_id' => UnitType::factory(),
            'project_id'   => fn (array $attributes) => UnitType::find($attributes['unit_type_id'])->project_id,
            'identifier'   => fake()->unique()->bothify('?-###'),
        ];
    }
}
