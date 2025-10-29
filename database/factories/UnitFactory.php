<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\UnitType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => $this->faker->numerify('###'),
            'project_id' => Project::factory(),
            'unit_type_id' => fn (array $attributes) => UnitType::factory()->create(['project_id' => $attributes['project_id']])->id,
            'family_id' => null,
        ];
    }

    public function inProject(Project $project): static
    {
        return $this->state([
            'number' => new Sequence(fn (Sequence $seq) => (string) ($seq->index + 1)),
            'project_id' => $project->id,
            'unit_type_id' => fn () => UnitType::factory()->create(['project_id' => $project->id])->id,
        ]);
    }
}
