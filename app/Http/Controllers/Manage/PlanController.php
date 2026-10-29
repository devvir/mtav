<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlanRequest;
use App\Models\Project;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PlanController extends Controller
{
    /**
     * The editor saves by itself as admins draw, asking for JSON: it learns the ids its new
     * shapes got, and the page stays as it is (no reload, no message each time)
     */
    public function update(PlanRequest $request, Project $project, PlanService $plans): RedirectResponse|JsonResponse
    {
        $created = $plans->update($project->plan, $request->validated());

        return $request->wantsJson()
            ? response()->json(['ids' => (object) $created])
            : back()->with('success', __('flash.plan_updated'));
    }
}
