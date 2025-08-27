<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Family>
 */
class FamilyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->lastName() . ' ' . $this->faker->lastName(),
        ];
    }

    public function withMembers(?int $min = null, ?int $max = null): static
    {
        $max ??= $min ?? 4;
        $min ??= 1;

        return $this->afterCreating(
            fn (Family $family) => User::factory()
                ->count(rand($min, $max))
                ->create([ 'family_id' => $family->getKey() ])
        );
    }

    public function inProject(?Project $project = null): static
    {
        $project ??= Project::inRandomOrder()->first() ?? Project::factory()->create();

        return $this->afterCreating($project->addFamily(...));
    }
}
