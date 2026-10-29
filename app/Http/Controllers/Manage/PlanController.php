<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlanRequest;
use App\Models\Project;
use App\Services\Plan\EditingLock;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlanController extends Controller
{
    /**
     * The editor saves by itself as admins draw, asking for JSON: it learns the ids its new
     * shapes got, and the page stays as it is (no reload, no message each time). Someone
     * else drawing it meanwhile holds it: the save is refused, never written over theirs.
     */
    public function update(PlanRequest $request, Project $project, PlanService $plans, EditingLock $lock): RedirectResponse|JsonResponse
    {
        if ($editor = $lock->heldByAnother($project->plan, $request->user())) {
            return $this->taken($editor->name);
        }

        $created = $plans->update($project->plan, $request->validated());

        return $request->wantsJson()
            ? response()->json(['ids' => (object) $created])
            : back()->with('success', __('flash.plan_updated'));
    }

    /** Starting to draw the plan, or still drawing it (the editor asks every half minute) */
    public function lock(Request $request, Project $project, EditingLock $lock): JsonResponse
    {
        $editor = $lock->take($project->plan, $request->user());

        return $editor ? $this->taken($editor->name) : response()->json(['held' => true]);
    }

    /** Done drawing: someone else may */
    public function unlock(Request $request, Project $project, EditingLock $lock): JsonResponse
    {
        $lock->release($project->plan, $request->user());

        return response()->json(['held' => false]);
    }

    private function taken(string $editor): JsonResponse
    {
        return response()->json(['editor' => $editor], Response::HTTP_LOCKED);
    }
}
