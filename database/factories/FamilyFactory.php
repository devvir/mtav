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
            'name' => $this->faker->lastName().' '.$this->faker->lastName(),
            'project_id' => Project::factory(),
        ];
    }

    public function withMembers(): static
    {
        return $this->afterCreating(
            fn (Family $family) => $family->members()->saveMany(
                tap(
                    User::factory()->count(rand(1, 6))->create(['family_id' => $family->id]),
                    fn ($users) => $family->project->members()->attach($users->pluck('id')))
            )
        );
    }

    public function inProject(Project $project): static
    {
        return $this->state(['project_id' => $project->id]);
    }
}
