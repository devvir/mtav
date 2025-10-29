<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Project;
use App\Models\UnitType;
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
            'name' => $this->faker->lastName().' '.$this->faker->lastName().' Family',
            'project_id' => Project::factory(),
            'unit_type_id' => fn (array $attributes) => UnitType::factory()->create(['project_id' => $attributes['project_id']])->id,
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
        return $this->state([
            'project_id' => $project->id,
            'unit_type_id' => fn () => UnitType::factory()->create(['project_id' => $project->id])->id,
        ]);
    }
}
