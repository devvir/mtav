<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitTypeRequest;
use App\Models\Project;
use App\Models\UnitType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Adding, renaming and removing unit types: admin tools on the units page.
 */
class UnitTypeController extends Controller
{
    public function store(UnitTypeRequest $request, Project $project): RedirectResponse
    {
        $unitType = $project->unitTypes()->create($request->validated());

        return back()->with('success', __('flash.unit_type_created'));
    }

    public function update(UnitTypeRequest $request, Project $project, UnitType $unitType): RedirectResponse
    {
        $unitType->update($request->validated());

        return back()->with('success', __('flash.unit_type_updated'));
    }

    public function destroy(Project $project, UnitType $unitType): RedirectResponse
    {
        if ($unitType->isInUse()) {
            throw ValidationException::withMessages(['unit_type' => __('validation.unit_type_in_use')]);
        }

        $unitType->delete();

        return back()->with('success', __('flash.unit_type_deleted'));
    }
}
