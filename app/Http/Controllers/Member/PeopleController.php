<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Models\Project;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\PeopleSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Who takes part in the project; admins change someone's role or family, or remove them,
 * from their page. From their own page, anyone leaves the project, and an admin gives the
 * role up.
 */
class PeopleController extends Controller
{
    use Concerns\ShowsDetails;

    public function index(Request $request, Project $project): Response
    {
        $manages = $request->user()->can('update', $project);

        [$people, $whole] = $this->wholeOrPaged(
            $project->members()
                ->withMembershipIn($project)
                ->alphabetically(),
            fn ($query) => $query
                ->when($request->q, fn ($q, $search) => $q->search($search, byEmail: $manages, nameOnly: $request->user()->isGuestIn($project)))
                ->when($request->integer('family'), fn ($q, $familyId) => $q->where('memberships.family_id', $familyId))
                ->when($request->boolean('admins'), fn ($q) => $q->where('memberships.is_admin', true)),
            config('lists.per_page.people'),
        );

        return inertia('Member/People/Index', [
            'people'  => MemberResource::collection($people),
            'whole'   => $whole,
            'filters' => [
                'q'      => $request->q,
                'family' => $request->integer('family') ?: null,
                'admins' => $request->boolean('admins'),
            ],
            'families' => $project->familyChoices(),
            'found'    => fn () => $request->filled('q') ? app(PeopleSearch::class)->find($project, $request->user(), (string) $request->q) : null,
            // Someone's details, shown over the list (?person=19)
            'person' => fn () => $this->personShown($request, $project),
        ]);
    }

    /** People's details open over the list: their address leads there */
    public function show(Project $project, User $member): RedirectResponse
    {
        return to_route('people.index', ['person' => $member->id]);
    }

    /**
     * Anyone may leave a project, except its last admin.
     */
    public function leave(Request $request, Project $project): RedirectResponse
    {
        $user = $request->user();
        $membership = $user->membershipIn($project);

        abort_unless($membership, 404);

        $project->holdingLottery(function () use ($project, $user, $membership) {
            $project->ensureLotteryNotRunning();
            $project->keepingAnAdmin($user, 'membership', fn () => $membership->delete());
        });

        return to_route('home')->with('success', __('flash.project_left', ['project' => $project->name]));
    }

    /**
     * An admin giving the role up (not the last one). With neither a family nor guests in the
     * project, it means leaving it, which the request says it means (`leave`).
     */
    public function renounce(Request $request, Project $project, MembershipService $memberships): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->membershipIn($project)?->is_admin, 404);

        if ($memberships->renounce($project, $user, $request->boolean('leave'))) {
            return to_route('home')->with('success', __('flash.project_left', ['project' => $project->name]));
        }

        return to_route('people.index', ['person' => $user->id])->with('success', __('flash.role_renounced'));
    }
}
