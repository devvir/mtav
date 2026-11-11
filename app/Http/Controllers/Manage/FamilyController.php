<?php

namespace App\Http\Controllers\Manage;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyRequest;
use App\Http\Requests\NewFamilyRequest;
use App\Models\Family;
use App\Models\Project;
use App\Services\InvitationService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Registering, changing and removing families: admin tools on the families pages everyone
 * uses. Each change holds the lottery still: a run starting at the same moment has it, or it
 * is refused.
 */
class FamilyController extends Controller
{
    /** A family comes with someone: alone, nobody could ever answer for it */
    public function store(NewFamilyRequest $request, Project $project, NotificationService $notifications, InvitationService $invitations): RedirectResponse
    {
        $family = $project->holdingLottery(function () use ($request, $project, $invitations) {
            $project->ensureLotteryNotRunning();

            $family = $project->families()->create($request->family());
            $invitations->invite($project, $request->user(), $request->person(), $family, asAdmin: false);

            return $family;
        });
        $notifications->bundle($project, NotificationType::FAMILIES_ADDED, 'families_added', $request->user(), route('families.index', $project));

        return to_route('families.index', ['family' => $family->id])->with('success', __('flash.family_created'));
    }

    public function update(FamilyRequest $request, Project $project, Family $family): RedirectResponse
    {
        $project->holdingLottery(function () use ($request, $project, $family) {
            if ($family->unit_type_id !== (int) $request->validated('unit_type_id') && $project->lotteryLocked()) {
                throw ValidationException::withMessages(['unit_type_id' => __('lottery.locked')]);
            }

            $family->update($request->validated());
        });

        return to_route('families.index', ['family' => $family->id])->with('success', __('flash.family_updated'));
    }

    /**
     * Removing a family archives it. One with people in it stays: they are moved or removed first,
     * one by one, knowing what happens to each. What is left to remove is one only waiting for
     * someone invited: an empty one is archived already.
     */
    public function destroy(Project $project, Family $family): RedirectResponse
    {
        $project->holdingLottery(function () use ($project, $family) {
            $project->ensureLotteryNotRunning();

            if ($family->unit()->exists()) {
                throw ValidationException::withMessages(['family' => __('validation.family_has_unit')]);
            }

            if ($family->memberships()->exists()) {
                throw ValidationException::withMessages(['family' => __('validation.family_has_people')]);
            }

            // Archived first: nobody is told of it emptying, the admin just removed it
            $family->delete();
            $family->joinRequests()->pending()->delete();
            $family->invitations()->pending()->delete();
        });

        return to_route('families.index')->with('success', __('flash.family_deleted'));
    }
}
