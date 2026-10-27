<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Models\Family;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

/**
 * The people of a project: their families and roles.
 */
class PeopleController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $members = $project->members()
            ->with(['memberships' => fn ($q) => $q->where('project_id', $project->id)->with('family:id,name')])
            ->when($request->q, fn ($q, $search) => $q->search($search))
            ->when($request->integer('family'), fn ($q, $familyId) => $q->wherePivot('family_id', $familyId))
            ->when($request->boolean('admins'), fn ($q) => $q->wherePivot('is_admin', true))
            ->alphabetically()
            ->paginate(30)
            ->withQueryString();

        return inertia('Manage/People', [
            'members'  => MemberResource::collection($members),
            'families' => $project->families()->alphabetically()->get(['id', 'name']),
            'filters'  => ['q' => $request->q, 'family' => $request->integer('family') ?: null, 'admins' => $request->boolean('admins')],
        ]);
    }


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

        return back()->with('success', __('flash.member_removed'));
    }

    private function ensureNotLastAdmin(Project $project, User $member): void
    {
        $admins = $project->admins()->pluck('users.id');

        if ($admins->count() === 1 && $admins->first() === $member->id) {
            throw ValidationException::withMessages(['member' => __('validation.last_admin')]);
        }
    }
}
