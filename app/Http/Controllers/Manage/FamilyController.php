<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyRequest;
use App\Http\Resources\FamilyResource;
use App\Http\Resources\UnitTypeResource;
use App\Models\Family;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class FamilyController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $families = $project->families()
            ->with('unitType:id,name', 'unit:id,family_id,identifier', 'members')
            ->withCount('members')
            ->when($request->q, fn ($q, $search) => $q->search($search))
            ->when($request->integer('unit_type'), fn ($q, $typeId) => $q->where('unit_type_id', $typeId))
            ->alphabetically()
            ->paginate(30)
            ->withQueryString();

        return inertia('Manage/Families/Index', [
            'families'  => FamilyResource::collection($families),
            'unitTypes' => $project->unitTypes()->alphabetically()->get(['id', 'name']),
            'filters'   => ['q' => $request->q, 'unit_type' => $request->integer('unit_type') ?: null],
        ]);
    }

    public function create(Project $project): Response
    {

        return inertia('Manage/Families/Create', ['unitTypes' => $this->unitTypes($project)]);
    }

    public function store(FamilyRequest $request, Project $project): RedirectResponse
    {

        $family = $project->families()->create($request->validated());

        return to_route('manage.families.index')->with('success', __('flash.family_created'));
    }

    public function edit(Project $project, Family $family): Response
    {

        return inertia('Manage/Families/Edit', [
            'family'    => FamilyResource::make($family->load('unitType')),
            'unitTypes' => $this->unitTypes($project),
        ]);
    }

    public function update(FamilyRequest $request, Project $project, Family $family): RedirectResponse
    {

        if ($family->unit_type_id !== (int) $request->validated('unit_type_id') && $project->lotteryLocked()) {
            throw ValidationException::withMessages(['unit_type_id' => __('lottery.locked')]);
        }

        $family->update($request->validated());

        return to_route('manage.families.index')->with('success', __('flash.family_updated'));
    }

    /**
     * Removing a family removes its members from the project too (admins stay admins).
     */
    public function destroy(Project $project, Family $family): RedirectResponse
    {

        if ($family->unit()->exists()) {
            throw ValidationException::withMessages(['family' => __('validation.family_has_unit')]);
        }

        DB::transaction(function () use ($family) {
            $family->memberships()->where('is_admin', false)->delete();
            $family->delete();
        });

        return to_route('manage.families.index')->with('success', __('flash.family_deleted'));
    }

    private function unitTypes(Project $project)
    {
        return UnitTypeResource::collection($project->unitTypes()->alphabetically()->get());
    }
}
