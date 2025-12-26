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
        if (! $plan = $unit->project->plan) {
            $plan = $this->addProject($unit->project);
        }

        return PlanItem::create([
            'plan_id' => $plan->id,
            'type'    => 'unit',
            'polygon' => $this->getNextAvailablePosition($plan),
            'floor'   => 0,
        ]);
    }

    /**
     * Calculate the next available position for a Unit in a grid layout.
     * Positions units in a 10-unit-per-row grid that fills the plan evenly.
     *
     * @return array<array{0: float, 1: float}>
     */
    private function getNextAvailablePosition(Plan $plan): array
    {
        // TODO : assumes all existing units have default positioning (will they though?)
        $existingUnits = $plan->items()->whereType('unit')->count();

        // Calculate plan boundaries from polygon (Point[] format: [[x,y], [x,y], ...])
        $polygon = $plan->polygon;
        $xs = array_column($polygon, 0);
        $ys = array_column($polygon, 1);
        $minX = min($xs);
        $minY = min($ys);
        $maxX = max($xs);

        // Grid configuration
        $boundaryMargin = 15;   // Space between grid and project boundary
        $unitsPerRow = 10;      // Fixed: always 10 units per row
        $unitHeight = 60;
        $unitMargin = 10;       // Space between units

        // Calculate available width and divide evenly among 10 units
        $availableWidth = ($maxX - $minX) - (2 * $boundaryMargin);
        $totalMarginWidth = ($unitsPerRow - 1) * $unitMargin;
        $unitWidth = ($availableWidth - $totalMarginWidth) / $unitsPerRow;

        // Calculate grid position
        $row = intval($existingUnits / $unitsPerRow);
        $col = $existingUnits % $unitsPerRow;

        $x = $minX + $boundaryMargin + ($col * ($unitWidth + $unitMargin));
        $y = $minY + $boundaryMargin + ($row * ($unitHeight + $unitMargin));

        return [
            [$x, $y],                                      // Top-left
            [$x + $unitWidth, $y],                         // Top-right
            [$x + $unitWidth, $y + $unitHeight],           // Bottom-right
            [$x, $y + $unitHeight],                        // Bottom-left
        ];
    }
}
