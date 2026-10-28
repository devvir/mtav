<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\FamilyResource;
use App\Http\Resources\UnitTypeResource;
use App\Models\Family;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Response;

class FamilyController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $families = $project->families()
            ->with('unitType:id,name', 'unit:id,family_id,identifier', 'members')
            ->withCount('members')
            ->when($request->q, fn ($q, $search) => $q->search($search))
            ->alphabetically()
            ->paginate(30)
            ->withQueryString();

        return inertia('Member/Families/Index', [
            'families'  => FamilyResource::collection($families),
            'filters'   => ['q' => $request->q],
            'unitTypes' => $this->unitTypesFor($request, $project),
        ]);
    }

    public function show(Request $request, Project $project, Family $family): Response
    {
        return inertia('Member/Families/Show', [
            'family'    => FamilyResource::make($family->load('unitType', 'unit', 'members')),
            'unitTypes' => $this->unitTypesFor($request, $project),
        ]);
    }

    /** For admins, who register and edit families here: the types a family can have */
    private function unitTypesFor(Request $request, Project $project)
    {
        return $request->user()->can('update', $project)
            ? UnitTypeResource::collection($project->unitTypes()->alphabetically()->get())
            : null;
    }
}
