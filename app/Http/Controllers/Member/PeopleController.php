<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

/**
 * Who takes part in the project; admins change someone's role or family, or remove them,
 * from their page.
 */
class PeopleController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $manages = $request->user()->can('update', $project);

        [$people, $whole] = $this->wholeOrPaged(
            $project->members()
                ->with(['memberships' => fn ($q) => $q->where('project_id', $project->id)->with('family:id,name')])
                ->alphabetically(),
            fn ($query) => $query
                ->when($request->q, fn ($q, $search) => $q->search($search, byEmail: $manages))
                ->when($request->integer('family'), fn ($q, $familyId) => $q->where('memberships.family_id', $familyId))
                ->when($request->boolean('admins'), fn ($q) => $q->where('memberships.is_admin', true)),
            40,
        );

        return inertia('Member/People/Index', [
            'people'  => MemberResource::collection($people),
            'whole'   => $whole,
            'filters' => [
                'q'      => $request->q,
                'family' => $request->integer('family') ?: null,
                'admins' => $request->boolean('admins'),
            ],
            'families' => $project->families()->alphabetically()->get(['id', 'name']),
            // Someone's details, shown over the list (?person=19)
            'person' => fn () => $request->integer('person')
                ? $this->details($request, $project, $request->integer('person'))
                : null,
        ]);
    }

    /** People's details open over the list: their address leads there */
    public function show(Project $project, User $member): RedirectResponse
    {
        return to_route('people.index', ['person' => $member->id]);
    }

    /**
     * @return array{person: MemberResource, families: mixed}|null
     */
    private function details(Request $request, Project $project, int $id): ?array
    {
        $member = $project->members()
            ->with(['memberships' => fn ($q) => $q->where('project_id', $project->id)->with('family')])
            ->find($id);

        return $member ? [
            'person' => MemberResource::make($member),
            // For admins, who change people's role and family here
            'families' => $request->user()->can('update', $project)
                ? $project->families()->alphabetically()->get(['id', 'name'])
                : null,
        ] : null;
    }

    /**
     * Anyone may leave a project, except its last admin.
     */
    public function leave(Request $request, Project $project): RedirectResponse
    {
        $user = $request->user();
        $membership = $user->membershipIn($project);

        abort_unless($membership, 404);

        if ($membership->is_admin && $project->admins()->count() === 1) {
            throw ValidationException::withMessages(['membership' => __('validation.last_admin')]);
        }

        $membership->delete();

        return to_route('home')->with('success', __('flash.project_left', ['project' => $project->name]));
    }
}
