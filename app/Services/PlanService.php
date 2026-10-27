<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Project;
use App\Models\Unit;
use App\Services\Plan\Defaults;
use Illuminate\Support\Facades\DB;

class PlanService
{
    public function __construct(private Defaults $defaults)
    {
    }

    /**
     * An empty 800×600 canvas whose boundary is the whole canvas.
     */
    public function createFor(Project $project): Plan
    {
        return $project->plan()->create([
            'polygon'     => [[0, 0], [800, 0], [800, 600], [0, 600]],
            'width'       => 800,
            'height'      => 600,
            'unit_system' => 'meters',
        ]);
    }

    /**
     * Give a new unit a square on the plan, next to the units placed before it.
     */
    public function place(Unit $unit): void
    {
        $plan = $unit->project->plan;

        $plan->items()->create([
            'unit_id' => $unit->id,
            'type'    => 'unit',
            'floor'   => 0,
            'polygon' => $this->defaults->getNextAvailablePosition($plan),
        ]);
    }

    /**
     * Save the boundary and the shapes of the plan's items.
     *
     * @param array<int, array{float, float}> $polygon
     * @param array<int, array{id: int, polygon: array<int, array{float, float}>}> $items
     */
    public function update(Plan $plan, array $polygon, array $items): void
    {
        DB::transaction(function () use ($plan, $polygon, $items) {
            $plan->update(['polygon' => $polygon]);

            foreach ($items as $item) {
                $plan->items()->whereKey($item['id'])->update(['polygon' => $item['polygon']]);
            }
        });
    }
}
