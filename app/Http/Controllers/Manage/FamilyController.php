<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyRequest;
use App\Models\Family;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registering, changing and removing families: admin tools on the families pages everyone
 * uses.
 */
class FamilyController extends Controller
{
    public function store(FamilyRequest $request, Project $project): RedirectResponse
    {
        $family = $project->families()->create($request->validated());

        return to_route('families.show', $family)->with('success', __('flash.family_created'));
    }

    public function update(FamilyRequest $request, Project $project, Family $family): RedirectResponse
    {
        if ($family->unit_type_id !== (int) $request->validated('unit_type_id') && $project->lotteryLocked()) {
            throw ValidationException::withMessages(['unit_type_id' => __('lottery.locked')]);
        }

        $family->update($request->validated());

        return back()->with('success', __('flash.family_updated'));
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

        return to_route('families.index')->with('success', __('flash.family_deleted'));
    }
}
