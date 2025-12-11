<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class CurrentProjectController
{
    public function set(Project $project): RedirectResponse
    {
        selectProject($project);

        return to_route('dashboard')->with('success', __('flash.project_set'));
    }

    public function unset(): RedirectResponse
    {
        unsetCurrentProject();

        return to_route('projects.index')->with('success', __('flash.project_unset'));
    }
}
