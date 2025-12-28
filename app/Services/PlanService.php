<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanItem;
use App\Models\Project;
use App\Models\Unit;
use App\Services\Plan\Defaults;
use App\Services\Plan\Polygons;

class PlanService
{
    public function __construct(
        private readonly Defaults $defaults,
        private readonly Polygons $polygons,
    ) {
        // ...
    }

    /**
     * Create a default plan for a Project.
     */
    public function addProject(Project $project, string $unitSystem = 'meters'): Plan
    {
        return Plan::create([
            'project_id'  => $project->id,
            'polygon'     => [[0, 0], [800, 0], [800, 600], [0, 600]],
            'width'       => 800,
            'height'      => 600,
            'unit_system' => $unitSystem,
        ]);
    }

    /**
     * Create a plan item for a Unit with default positioning.
     */
    public function addUnit(Unit $unit): PlanItem
    {
        $plan = $unit->project->plan;

        return PlanItem::create([
            'plan_id' => $plan->id,
            'type'    => 'unit',
            'polygon' => $this->defaults->getNextAvailablePosition($plan),
            'floor'   => 0,
        ]);
    }

    /**
     * Update Plan boundary and PlanItem polygons.
     *
     * @param  Plan  $plan
     * @param  array{
     *   polygon: array<int, array{float, float}>,
     *   items: array<int, array{id: int, polygon: array<int, array{float, float}>}>
     * }  $data
     */
    public function updatePlan(Plan $plan, array $data): void
    {
        $this->polygons->update($plan, $data['polygon'], $data['items']);
    }
}
