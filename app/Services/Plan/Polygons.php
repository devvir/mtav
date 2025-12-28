<?php

namespace App\Services\Plan;

use App\Models\Plan;
use Illuminate\Support\Facades\DB;

class Polygons
{
    /**
     * Update Plan boundary and PlanItem polygons atomically.
     *
     * @param  Plan  $plan
     * @param  array<int, array{float, float}>  $planPolygon
     * @param  array<int, array{
     *   id: int,
     *   polygon: array<int, array{float, float}>
     * }>  $items
     */
    public function update(Plan $plan, array $planPolygon, array $items): void
    {
        DB::transaction(function () use ($plan, $planPolygon, $items) {
            $plan->update(['polygon' => $planPolygon]);

            foreach ($items as $itemData) {
                $plan->items()
                    ->where('id', $itemData['id'])
                    ->update(['polygon' => $itemData['polygon']]);
            }
        });
    }
}
