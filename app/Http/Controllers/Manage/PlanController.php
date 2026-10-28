<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlanRequest;
use App\Models\Project;
use App\Services\PlanService;
use Illuminate\Http\RedirectResponse;

class PlanController extends Controller
{
    public function update(PlanRequest $request, Project $project, PlanService $plans): RedirectResponse
    {
        $plans->update($project->plan, $request->validated());

        return back()->with('success', __('flash.plan_updated'));
    }
}
