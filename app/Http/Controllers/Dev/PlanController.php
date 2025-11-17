<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;

class PlanController extends Controller
{
    public function __invoke()
    {
        $project = Project::with(['plan.items.unit.type', 'unitTypes'])->first();

        return inertia('Dev/Plans', [
            'project' => $project ? new ProjectResource($project) : null,
        ]);
    }
}
