<?php

namespace Database\Factories\Concerns;

use App\Models\Project;
use Closure;

trait InProject
{
    public function inProject(Project|int $project): static
    {
        return $this->state([
            'project_id' => $project->id ?? $project,
        ]);
    }

    /**
     * Force related models to belong to the same Project as the current instance.
     *
     * Usage 1: ->state($this->inSameProject(UnitType::class))
     * Usage 2: 'unit_type_id' => $this->inSameProject(UnitType::class)
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    protected function inSameProject(string $model): Closure
    {
        return fn (array $attributes) => $model::factory()->inProject($attributes['project_id']);
    }
}
