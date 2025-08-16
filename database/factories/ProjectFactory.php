<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
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
            'name' => $this->faker->company(),
        ];
    }

    public function withMembers(?int $min = null, ?int $max = null): static
    {
        $max ??= $min ?? 10;
        $min ??= 3;

        $defaultAdmin = User::firstWhere('email', 'admin@example.com');

        return $this
            ->afterCreating(
                fn (Project $project) => $project->addUser($defaultAdmin)
            )
            ->afterCreating(
                fn (Project $project) => $project->users()->attach(
                    User::factory()
                        ->withExistingFamily()
                        ->count(rand($min, $max))
                        ->create()
                )
            );
    }

    public function withUnits(?int $min = null, ?int $max = null): static
    {
        $max ??= $min ?? 30;
        $min ??= 10;

        return $this->afterCreating(
            fn (Project $project) => $project->units()->saveMany(
                Unit::factory()->count(rand($min, $max))->inProject($project)->create()
            )
        );
    }
}
