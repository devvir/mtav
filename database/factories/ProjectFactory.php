<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Project;
use App\Models\Unit;
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
            'name'         => $this->faker->company(),
            'description'  => $this->faker->sentence(10),
            'organization' => $this->faker->randomElement(['FECOVI', 'FUCVAM', 'SUNCA']),
            'active'       => rand(1, 10) < 8,
        ];
    }

    public function configure()
    {
        $defaultAdmin = once(fn () => Admin::firstWhere('email', 'admin@example.com'));

        if (! $defaultAdmin) {
            return $this;
        }

        return $this->afterCreating(
            fn (Project $project) => $project->addAdmin($defaultAdmin)
        );
    }

    public function withUnits(): static
    {
        $units = Unit::factory()->count(random_int(5, 20))->create();

        return $this->afterCreating(
            fn (Project $project) => $project->units()->saveMany($units)
        );
    }
}
