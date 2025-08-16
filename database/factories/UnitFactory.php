<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Unit;
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
            'name' => $this->faker->unique()->word(),
            'project_id' => Project::factory(),
        ];
    }

    public function inProject(Project $project): static
    {
        return $this->state([
            'name' => new Sequence(fn (Sequence $seq) => 'Unit ' . $project->id . '-' . $seq->index + 1),
            'project_id' => $project,
        ]);
    }
}
