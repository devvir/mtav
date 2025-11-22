<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\UnitType;
use Database\Factories\Concerns\InProject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    use InProject;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id'   => Project::factory(),
            'unit_type_id' => $this->inSameProject(UnitType::class),
        ];
    }

    public function configure()
    {
        static $unitsCreated = 0;

        return $this->sequence(
            fn (Sequence $sequence) => [
                'identifier' => __('Unit') . ' ' . (++$unitsCreated + $sequence->index),
            ]
        );
    }

    public function withType(UnitType|int $unitType): static
    {
        return $this->state([
            'unit_type_id' => $unitType instanceof UnitType ? $unitType->id : $unitType,
        ]);
    }
}
