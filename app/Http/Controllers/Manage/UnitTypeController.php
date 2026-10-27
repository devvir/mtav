<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitTypeRequest;
use App\Http\Resources\UnitTypeResource;
use App\Models\Project;
use App\Models\UnitType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class UnitTypeController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        return inertia('Manage/UnitTypes/Index', [
            'unitTypes' => UnitTypeResource::collection(
                $project->unitTypes()->withCount('units', 'families')->alphabetically()->get()
            ),
        ]);
    }

    public function create(Project $project): Response
    {

        return inertia('Manage/UnitTypes/Create');
    }

    public function store(UnitTypeRequest $request, Project $project): RedirectResponse
    {

        $unitType = $project->unitTypes()->create($request->validated());

        return to_route('manage.unit_types.index')->with('success', __('flash.unit_type_created'));
    }

    public function edit(Project $project, UnitType $unitType): Response
    {

        return inertia('Manage/UnitTypes/Edit', ['unitType' => UnitTypeResource::make($unitType)]);
    }

    public function update(UnitTypeRequest $request, Project $project, UnitType $unitType): RedirectResponse
    {

        $unitType->update($request->validated());

        return to_route('manage.unit_types.index')->with('success', __('flash.unit_type_updated'));
    }

    public function destroy(Project $project, UnitType $unitType): RedirectResponse
    {

        if ($unitType->isInUse()) {
            throw ValidationException::withMessages(['unit_type' => __('validation.unit_type_in_use')]);
        }

        $unitType->delete();

        return to_route('manage.unit_types.index')->with('success', __('flash.unit_type_deleted'));
    }
}
