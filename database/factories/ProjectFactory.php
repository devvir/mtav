<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'         => 'Cooperativa ' . fake()->unique()->lastName(),
            'description'  => fake()->sentence(12),
            'organization' => fake()->randomElement(['FUCVAM', 'FECOVI', null]),
            'active'       => true,
            'approved_at'  => now(),
        ];
    }

    public function unapproved(): static
    {
        return $this->state(['approved_at' => null]);
    }
}
