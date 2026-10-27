<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Response;

class PlanController extends Controller
{
    public function __invoke(Request $request, Project $project): Response
    {
        return inertia('Member/Plan', [
            'plan'   => PlanResource::make($project->plan->load('items.unit.family')),
            'family' => $request->user()->familyIn($project)?->only('id', 'name', 'unit_type_id'),
        ]);
    }
}
