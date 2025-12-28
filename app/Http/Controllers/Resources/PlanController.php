<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\UpdatePlanRequest;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class PlanController extends Controller
{
    public function edit(Plan $plan): Response
    {
        return inertia('Plans/Edit', [
            'plan' => $plan->load('project', 'items.unit.type'),
        ]);
    }

    public function update(UpdatePlanRequest $request, PlanService $planService, Plan $plan): RedirectResponse
    {
        $planService->updatePlan($plan, $request->validated());

        return to_route('plans.edit', $plan)->with('success', __('flash.plan_updated'));
    }
}
