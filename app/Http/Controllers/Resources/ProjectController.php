<?php

namespace App\Http\Controllers\Resources;

use App\Http\Controllers\ResourceController;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends ResourceController
{
    /**
     * Show the project dashboard.
     */
    public function index(Request $request)
    {
        $projects = $request->user()->isSuperAdmin()
            ? Project::all()
            : $request->user()->projects()->active()->get();

        return inertia('Projects/Index', compact('projects'));
    }

    /**
     * Show the project details.
     */
    public function show(Request $request, Project $project)
    {
        return inertia('Projects/Show', compact('project'));
    }
}
