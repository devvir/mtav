<?php

namespace App\Http\Controllers\Manage;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyRequest;
use App\Models\Family;
use App\Models\Project;
use App\Services\InvitationService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registering, changing and removing families: admin tools on the families pages everyone
 * uses.
 */
class FamilyController extends Controller
{
    /** A family comes with someone: alone, nobody could ever answer for it */
    public function store(FamilyRequest $request, Project $project, NotificationService $notifications, InvitationService $invitations): RedirectResponse
    {
        $project->ensureLotteryNotRunning();

        $person = $request->validate([
            'email'     => ['required', 'email', 'max:255'],
            'firstname' => ['nullable', 'string', 'between:2,80'],
            'lastname'  => ['nullable', 'string', 'between:2,80'],
        ]);

        $family = DB::transaction(function () use ($request, $project, $invitations, $person) {
            $family = $project->families()->create($request->safe()->only('name', 'unit_type_id'));
            $invitations->invite($project, $request->user(), $person, $family, asAdmin: false);

            return $family;
        });
        $notifications->bundle($project, NotificationType::FAMILIES_ADDED, 'families_added', $request->user(), route('families.index', $project));

        return to_route('families.index', ['family' => $family->id])->with('success', __('flash.family_created'));
    }

    public function update(FamilyRequest $request, Project $project, Family $family): RedirectResponse
    {
        if ($family->unit_type_id !== (int) $request->validated('unit_type_id') && $project->lotteryLocked()) {
            throw ValidationException::withMessages(['unit_type_id' => __('lottery.locked')]);
        }

        $family->update($request->validated());

        return to_route('families.index', ['family' => $family->id])->with('success', __('flash.family_updated'));
    }

    /**
     * Removing a family archives it. One with people in it stays: they are moved or removed first,
     * one by one, knowing what happens to each. What is left to remove is one only waiting for
     * someone invited: an empty one is archived already.
     */
    public function destroy(Project $project, Family $family): RedirectResponse
    {
        $project->ensureLotteryNotRunning();

        if ($family->unit()->exists()) {
            throw ValidationException::withMessages(['family' => __('validation.family_has_unit')]);
        }

        if ($family->memberships()->exists()) {
            throw ValidationException::withMessages(['family' => __('validation.family_has_people')]);
        }

        DB::transaction(function () use ($family) {
            // Archived first: nobody is told of it emptying, the admin just removed it
            $family->delete();
            $family->joinRequests()->pending()->delete();
            $family->invitations()->pending()->delete();
        });

        return to_route('families.index')->with('success', __('flash.family_deleted'));
    }
}
