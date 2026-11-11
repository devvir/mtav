<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyJoinRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The people of a project: their families and roles.
 */
class PeopleController extends Controller
{
    /**
     * Admins move people between families and grant or revoke the admin role.
     */
    public function update(Request $request, Project $project, User $member, InvitationService $invitations): RedirectResponse
    {
        $data = $request->validate([
            'family_id' => ['nullable', 'integer', Rule::exists(Family::class, 'id')->where('project_id', $project->id)],
            'is_admin'  => ['required', 'boolean'],
        ]);

        $membership = $member->membershipIn($project);
        // The form sends ids as text: compared as numbers, a role changed alone moves nobody
        $familyId = isset($data['family_id']) ? (int) $data['family_id'] : null;
        $data['family_id'] = $familyId;
        $moved = $familyId !== ($membership->family_id ? (int) $membership->family_id : null);
        $demoted = $membership->is_admin && ! $data['is_admin'];

        if (! $data['is_admin'] && ! $familyId && ! $project->allows_guests) {
            throw ValidationException::withMessages(['family_id' => __('validation.member_needs_family')]);
        }

        if ($moved) {
            $project->ensureLotteryNotRunning();
        }

        if ($moved && $familyId) {
            // A family's decline stands against admins too: only someone in it lifts it
            if (FamilyJoinRequest::declined()->where('family_id', $familyId)->where('user_id', $member->id)->exists()) {
                throw ValidationException::withMessages(['family_id' => __('validation.family_declined_them')]);
            }

            // Moving someone into an archived family brings it back: it takes room again
            if (Family::onlyTrashed()->whereKey($familyId)->exists()) {
                $project->ensureRoomForFamily('family_id');
            }
        }

        $apply = function () use ($project, $member, $membership, $data, $moved, $demoted) {
            $membership->update($data);

            // What they were invited to no longer stands: it would undo this, on accepting
            $project->invitations()->pending()->where('email', $member->email)
                ->where(fn ($invitation) => $invitation
                    ->when($demoted, fn ($query) => $query->orWhere('is_admin', true))
                    ->when($moved, fn ($query) => $query->orWhereNotNull('family_id'))
                    ->orWhereRaw('1 = 0'))
                // One by one: a family waiting only for one of them may have nobody left
                ->get()->each->delete();
        };

        $demoted ? $project->keepingAnAdmin($member, 'member', $apply) : DB::transaction($apply);

        if ($moved && $familyId) {
            $invitations->tellAddedToFamily($membership->fresh(), $request->user());
        }

        return to_route('people.index', ['person' => $member->id])->with('success', __('flash.member_updated'));
    }

    public function destroy(Project $project, User $member): RedirectResponse
    {
        $project->ensureLotteryNotRunning();

        $project->keepingAnAdmin($member, 'member', function () use ($project, $member) {
            $member->membershipIn($project)->delete();
            // An old invitation would bring them back, role and all (one by one: a family waiting
            // only for it may have nobody left)
            $project->invitations()->pending()->where('email', $member->email)->get()->each->delete();
        });

        return to_route('people.index')->with('success', __('flash.member_removed'));
    }
}
