<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Project $project): Response
    {
        return inertia('Manage/Settings', ['settings' => ProjectResource::make($project)]);
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return back()->with('success', __('flash.project_updated'));
    }

    /**
     * Deleting a project takes everything in it; the admin types its name to confirm.
     */
    public function destroy(Request $request, Project $project, ProjectService $projects): RedirectResponse
    {
        $this->authorize('delete', $project);

        $request->validate(['confirmation' => ['required', Rule::in([$project->name])]]);

        // Archived or deleted for good, it is gone for them either way
        $projects->remove($project, $request->user());

        return to_route('projects.index')->with('success', __('flash.project_deleted'));
    }

    public function requestApproval(Request $request, Project $project, ProjectService $projects): RedirectResponse
    {
        $this->authorize('requestApproval', $project);

        $projects->requestApproval($project, $request->user());

        return back()->with('success', __('flash.approval_requested', ['app' => config('app.name')]));
    }
}
