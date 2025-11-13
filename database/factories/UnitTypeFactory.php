<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\UnitType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UnitType>
 */
class UnitTypeFactory extends Factory
{
    protected $model = UnitType::class;

    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'name'        => $this->faker->word(),
            'description' => $this->faker->sentence(),
        ];
    }
}
