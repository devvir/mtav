<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\MembershipRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The people of a project: their families and roles.
 */
class PeopleController extends Controller
{
    /**
     * Admins move people between families and grant the admin role; only superadmins take it
     * back, or remove an admin.
     */
    public function update(MembershipRequest $request, Project $project, User $member, MembershipService $memberships): RedirectResponse
    {
        $memberships->place($project, $member, $request->familyId(), $request->asAdmin(), $request->user());

        return to_route('people.index', ['person' => $member->id])->with('success', __('flash.member_updated'));
    }

    public function destroy(Request $request, Project $project, User $member, MembershipService $memberships): RedirectResponse
    {
        $memberships->remove($project, $member, $request->user());

        return to_route('people.index')->with('success', __('flash.member_removed'));
    }
}
