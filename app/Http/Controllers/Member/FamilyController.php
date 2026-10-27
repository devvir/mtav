<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\FamilyResource;
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
            'families' => FamilyResource::collection($families),
            'filters'  => ['q' => $request->q],
        ]);
    }

    public function show(Project $project, Family $family): Response
    {
        return inertia('Member/Families/Show', [
            'family' => FamilyResource::make($family->load('unitType', 'unit', 'members')),
        ]);
    }
}
