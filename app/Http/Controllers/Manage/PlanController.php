<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Project;
use App\Services\PlanService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class PlanController extends Controller
{
    public function edit(Project $project): Response
    {
        $plan = $project->plan->load('items.unit.family');

        return inertia('Manage/Plan', ['plan' => PlanResource::make($plan)]);
    }

    public function update(PlanRequest $request, Project $project, PlanService $plans): RedirectResponse
    {
        $plans->update($project->plan, $request->validated('polygon'), $request->validated('items'));

        return back()->with('success', __('flash.plan_updated'));
    }
}
