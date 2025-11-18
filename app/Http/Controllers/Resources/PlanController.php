<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\UpdatePlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class PlanController extends Controller
{
    public function show(Plan $plan): Response
    {
        return inertia('Plans/Show', [
            'plan' => $plan->load('project.unitTypes', 'items.unit'),
        ]);
    }

    public function edit(Plan $plan): Response
    {
        return inertia('Plans/Edit', [
            'plan' => $plan->load('project', 'items.unit.type'),
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($request->validated());

        return to_route('plans.show', $plan)
            ->with('success', __('flash.plan_updated'));
    }
}
