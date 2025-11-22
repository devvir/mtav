<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'         => fake()->unique()->company(),
            'description'  => fake()->sentence(10),
            'organization' => fake()->randomElement(['FECOVI', 'FUCVAM', 'SUNCA']),
            'active'       => fake()->boolean(90),
        ];
    }

    public function configure()
    {
        $defaultAdmin = once(fn () => Admin::firstWhere('email', 'admin@example.com'));

        if (! $defaultAdmin) {
            return $this;
        }

        return $this->afterCreating(fn (Project $project) => $project->addAdmin($defaultAdmin));
    }
}
