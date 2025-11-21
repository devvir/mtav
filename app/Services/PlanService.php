<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanItem;
use App\Models\Project;
use App\Models\Unit;

class PlanService
{
    /**
     * Create a default plan for a Project.
     */
    public function addProject(Project $project, string $unitSystem = 'meters'): Plan
    {
        return Plan::create([
            'project_id'  => $project->id,
            'polygon'     => [0, 0, 800, 0, 800, 600, 0, 600],
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
        if (! $plan = $unit->project->plan) {
            $plan = $this->addProject($unit->project);
        }

        return PlanItem::create([
            'plan_id' => $plan->id,
            'type'    => 'unit',
            'polygon' => $this->getNextAvailablePosition($plan),
            'floor'   => 0,
            'name'    => null,
        ]);
    }

    /**
     * Calculate the next available position for a Unit in a grid layout.
     *
     * @return array{x: int, y: int, z: int}
     */
    private function getNextAvailablePosition(Plan $plan): array
    {
        // TODO : assumes all existing units have default positioning (will they though?)
        $existingUnits = $plan->items()->whereType('unit')->count();

        // Grid configuration
        $unitsPerRow = 4;
        $unitWidth = 80;
        $unitHeight = 60;
        $margin = 10;
        $offsetX = 50;
        $offsetY = 50;

        // Calculate grid position
        $row = intval($existingUnits / $unitsPerRow);
        $col = $existingUnits % $unitsPerRow;

        $x = $col * ($unitWidth + $margin) + $offsetX;
        $y = $row * ($unitHeight + $margin) + $offsetY;

        return [
            $x, $y,                             // Top-left
            $x + $unitWidth, $y,                // Top-right
            $x + $unitWidth, $y + $unitHeight,  // Bottom-right
            $x, $y + $unitHeight,               // Bottom-left
        ];
    }
}
