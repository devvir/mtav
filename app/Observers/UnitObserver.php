<?php

namespace App\Observers;

use App\Models\Unit;
use App\Services\PlanService;

class UnitObserver
{
    public function __construct(
        private PlanService $planService
    ) {
        // ...
    }

    /**
     * Automatically create a plan item for each new unit.
     */
    public function creating(Unit $unit): void
    {
        $unit->plan_item_id = $this->planService->addUnit($unit)->id;
    }
}
