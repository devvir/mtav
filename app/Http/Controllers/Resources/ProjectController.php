<?php

namespace App\Http\Controllers\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    /**
     * Show the project dashboard.
     */
    public function index(Request $request)
    {
        $projects = $request->user()->isSuperAdmin()
            ? Project::query()
            : $request->user()->projects();

        return inertia('Projects/Index', [
            'projects' => Inertia::deepMerge(fn () => $projects->paginate()->withQueryString()),
        ]);
    }

    /**
     * Show the project details.
     */
    public function show(Request $request, Project $project)
    {
        return inertia('Projects/Show', compact('project'));
    }
}
