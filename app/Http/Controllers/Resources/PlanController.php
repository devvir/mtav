<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\FilteredIndexRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(FilteredIndexRequest $request): Response
    {
        $plans = currentProject()->plan()
            ->with('project', 'items.unit.type')
            ->when($request->q, fn ($q, $search) => $q->search($search));

        return inertia('Plans/Index', [
            'plans' => $plans->paginate(30),
        ]);
    }

    public function show(Plan $plan): Response
    {
        return inertia('Plans/Show', [
            'plan' => $plan->load('project.unitTypes', 'items.unit'),
        ]);
    }

    public function create(): Response
    {
        return inertia('Plans/Create');
    }

    public function store(): RedirectResponse
    {
        $validated = request()->validate([
            'polygon'     => 'required|json',
            'width'       => 'required|numeric|min:1',
            'height'      => 'required|numeric|min:1',
            'unit_system' => 'required|string|in:meters,feet,inches',
        ]);

        $plan = currentProject()->plan()->create($validated);

        return to_route('plans.show', $plan)
            ->with('success', __('flash.plan_created'));
    }

    public function edit(Plan $plan): Response
    {
        return inertia('Plans/Edit', [
            'plan' => $plan->load('project', 'items.unit.type'),
        ]);
    }

    public function update(Plan $plan): RedirectResponse
    {
        $validated = request()->validate([
            'polygon'     => 'required|json',
            'width'       => 'required|numeric|min:1',
            'height'      => 'required|numeric|min:1',
            'unit_system' => 'required|string|in:meters,feet,inches',
        ]);

        $plan->update($validated);

        return to_route('plans.show', $plan)
            ->with('success', __('flash.plan_updated'));
    }
}
