<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\FamilyResource;
use App\Http\Resources\UnitTypeResource;
use App\Models\Family;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class FamilyController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        [$families, $whole] = $this->wholeOrPaged(
            $project->families()
                ->with('unitType:id,name', 'unit:id,family_id,identifier', 'members')
                ->withCount('members')
                ->alphabetically(),
            fn ($query) => $query
                ->when($request->q, fn ($q, $search) => $q->search($search, byEmail: $request->user()->can('update', $project)))
                ->when($request->integer('unit_type'), fn ($q, $typeId) => $q->where('unit_type_id', $typeId)),
            30,
        );

        return inertia('Member/Families/Index', [
            'families'  => FamilyResource::collection($families),
            'whole'     => $whole,
            'filters'   => ['q' => $request->q, 'unit_type' => $request->integer('unit_type') ?: null],
            'types'     => $project->unitTypes()->alphabetically()->get(['id', 'name']),
            'unitTypes' => $this->unitTypesFor($request, $project),
            // A family's details, shown over the list (?family=12)
            'family' => fn () => $request->integer('family') && ($family = $project->families()->find($request->integer('family')))
                ? FamilyResource::make($family->load('unitType', 'unit', 'members'))
                : null,
        ]);
    }

    /** A family's details open over the list: its address leads there */
    public function show(Project $project, Family $family): RedirectResponse
    {
        return to_route('families.index', ['family' => $family->id]);
    }

    /** For admins, who register and edit families here: the types a family can have */
    private function unitTypesFor(Request $request, Project $project)
    {
        return $request->user()->can('update', $project)
            ? UnitTypeResource::collection($project->unitTypes()->alphabetically()->get())
            : null;
    }
}
