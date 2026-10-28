<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function update(Request $request, Project $project, User $member): RedirectResponse
    {
        $data = $request->validate([
            'family_id' => ['nullable', Rule::exists(Family::class, 'id')->where('project_id', $project->id)],
            'is_admin'  => ['required', 'boolean'],
        ]);

        $membership = $member->membershipIn($project);

        if (! $data['is_admin'] && ! $data['family_id']) {
            throw ValidationException::withMessages(['family_id' => __('validation.member_needs_family')]);
        }

        if ($membership->is_admin && ! $data['is_admin']) {
            $this->ensureNotLastAdmin($project, $member);
        }

        $membership->update($data);

        return back()->with('success', __('flash.member_updated'));
    }

    public function destroy(Project $project, User $member): RedirectResponse
    {
        $this->ensureNotLastAdmin($project, $member);

        $member->membershipIn($project)->delete();

        return to_route('people.index')->with('success', __('flash.member_removed'));
    }

    private function ensureNotLastAdmin(Project $project, User $member): void
    {
        $admins = $project->admins()->pluck('users.id');

        if ($admins->count() === 1 && $admins->first() === $member->id) {
            throw ValidationException::withMessages(['member' => __('validation.last_admin')]);
        }
    }
}
