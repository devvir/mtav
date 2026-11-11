<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\MembershipRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Http\RedirectResponse;

/**
 * The people of a project: their families and roles.
 */
class PeopleController extends Controller
{
    /**
     * Admins move people between families and grant or revoke the admin role.
     */
    public function update(MembershipRequest $request, Project $project, User $member, MembershipService $memberships): RedirectResponse
    {
        $memberships->place($project, $member, $request->familyId(), $request->asAdmin(), $request->user());

        return to_route('people.index', ['person' => $member->id])->with('success', __('flash.member_updated'));
    }

    public function destroy(Project $project, User $member, MembershipService $memberships): RedirectResponse
    {
        $memberships->remove($project, $member);

        return to_route('people.index')->with('success', __('flash.member_removed'));
    }
}
