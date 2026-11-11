<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyJoinRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Admins placing someone in the project: moving them between families (or out of one, where
 * guests are welcome), granting or taking back the admin role, or removing them. A move or a
 * removal holds the lottery still: a run starting at the same moment has it, or it is refused.
 */
class MembershipService
{
    public function __construct(private InvitationService $invitations)
    {
    }

    /**
     * @throws ValidationException when they would be left with neither a family nor a role
     *                             without guests, the family declined them, a run is being
     *                             solved, or it would take the project's only admin away
     */
    public function place(Project $project, User $person, ?int $familyId, bool $asAdmin, User $by): void
    {
        $membership = $person->membershipIn($project);
        $moved = $familyId !== ($membership->family_id ? (int) $membership->family_id : null);
        $demoted = $membership->is_admin && ! $asAdmin;

        if (! $asAdmin && ! $familyId && ! $project->allows_guests) {
            throw ValidationException::withMessages(['family_id' => __('validation.member_needs_family')]);
        }

        $project->holdingLottery(function () use ($project, $person, $membership, $familyId, $asAdmin, $moved, $demoted) {
            $moved && $this->ensureCanMove($project, $person, $familyId);

            $apply = function () use ($project, $person, $membership, $familyId, $asAdmin, $moved, $demoted) {
                $membership->update(['family_id' => $familyId, 'is_admin' => $asAdmin]);
                $this->dropInvitationsUndoing($project, $person, $moved, $demoted);
            };

            $demoted ? $project->keepingAnAdmin($person, 'member', $apply) : $apply();
        });

        if ($moved && $familyId) {
            $this->invitations->tellAddedToFamily($membership->fresh(), $by);
        }
    }

    /**
     * Out of the project, and out of what they were invited to: an old invitation would bring
     * them back, role and all.
     *
     * @throws ValidationException when a run is being solved, or they are its only admin
     */
    public function remove(Project $project, User $person): void
    {
        $project->holdingLottery(function () use ($project, $person) {
            $project->ensureLotteryNotRunning();

            $project->keepingAnAdmin($person, 'member', function () use ($project, $person) {
                $person->membershipIn($project)->delete();
                // One by one: a family waiting only for one of them may have nobody left
                $project->invitations()->pending()->where('email', $person->email)->get()->each->delete();
            });
        });
    }

    /** @throws ValidationException */
    private function ensureCanMove(Project $project, User $person, ?int $familyId): void
    {
        $project->ensureLotteryNotRunning();

        if (! $familyId) {
            return;
        }

        // A family's decline stands against admins too: only someone in it lifts it
        if (FamilyJoinRequest::declined()->where('family_id', $familyId)->where('user_id', $person->id)->exists()) {
            throw ValidationException::withMessages(['family_id' => __('validation.family_declined_them')]);
        }

        // Moving someone into an archived family brings it back: it takes room again
        if (Family::onlyTrashed()->whereKey($familyId)->exists()) {
            $project->ensureRoomForFamily('family_id');
        }
    }

    /** What they were invited to no longer stands: it would undo this, on accepting */
    private function dropInvitationsUndoing(Project $project, User $person, bool $moved, bool $demoted): void
    {
        $project->invitations()->pending()->where('email', $person->email)
            ->where(fn ($invitation) => $invitation
                ->when($demoted, fn ($query) => $query->orWhere('is_admin', true))
                ->when($moved, fn ($query) => $query->orWhereNotNull('family_id'))
                ->orWhereRaw('1 = 0'))
            // One by one: a family waiting only for one of them may have nobody left
            ->get()->each->delete();
    }
}
