<?php

namespace App\Http\Controllers\Member;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyRequest;
use App\Http\Resources\FamilyResource;
use App\Http\Resources\UnitTypeResource;
use App\Models\Family;
use App\Models\FamilyJoinRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\ArchiveService;
use App\Services\FamilyService;
use App\Services\NotificationService;
use App\Services\PeopleSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class FamilyController extends Controller
{
    use Concerns\ShowsDetails;

    public function __construct(private NotificationService $notifications)
    {
    }

    public function index(Request $request, Project $project, ArchiveService $archive): Response
    {
        [$families] = $this->wholeOrPaged(
            $project->families()
                ->with('unitType:id,name', 'unit:id,family_id,identifier', 'members')
                ->withCount('members')
                ->alphabetically(),
            fn ($query) => $query
                ->when($request->q, fn ($q, $search) => $q->search($search, byEmail: $request->user()->can('update', $project), nameOnly: $request->user()->isGuestIn($project))),
            config('lists.per_page.families'),
        );

        return inertia('Member/Families/Index', [
            'families'  => FamilyResource::collection($families),
            'filters'   => ['q' => $request->q],
            'found'     => fn () => $request->filled('q') ? app(PeopleSearch::class)->find($project, $request->user(), (string) $request->q) : null,
            'unitTypes' => $this->unitTypesFor($request, $project),
            // A family's details, shown over the list (?family=12)
            'family' => fn () => $this->familyShown($request, $project),
            // For those in it, who answer them: who asks to join, and who was declined
            'requests' => fn () => $this->requestsShown($request, $project),
            'archive'  => fn () => $archive->forPage($request, $project, 'families'),
            // Someone to bring an archived family back with
            'guests' => fn () => $request->user()->can('update', $project) && $request->boolean('archived')
                ? $project->memberships()->whereNull('family_id')->where('is_admin', false)->with('user:id,firstname,lastname,email')->get()
                    ->map(fn ($membership) => ['name' => $membership->user->name, 'email' => $membership->user->email])
                : [],
        ]);
    }

    /**
     * Finding one's family, or starting it: not joined yet (let in without guests it comes
     * first: ResolveProject brings them here), or joined in the wrong one. Names only: nobody's contact
     * details, to someone who may not belong here.
     */
    public function create(Request $request, Project $project): Response
    {
        $user = $request->user();
        $own = $user->familyIn($project);
        $asked = FamilyJoinRequest::where('user_id', $user->id)->where('project_id', $project->id)->get();

        return inertia('Member/OwnFamily', [
            'types' => $user->can('createOwnFamily', $project)
                ? $project->unitTypes()->alphabetically()->get(['id', 'name'])
                : null,
            'families' => $project->families()->with('unitType:id,name', 'members:id,firstname,lastname')->alphabetically()->get()
                ->reject(fn (Family $family) => $family->is($own))
                ->values()
                ->map(fn (Family $family) => [
                    'id'        => $family->id,
                    'name'      => $family->name,
                    'unit_type' => $family->unitType?->name,
                    'people'    => $family->members->map(fn (User $person) => $person->name),
                    'request'   => $asked->firstWhere('family_id', $family->id)?->only('id', 'declined_at'),
                ]),
            'own' => $own ? [
                'name'  => $own->name,
                'alone' => $own->memberships()->count() === 1,
            ] : null,
            'can_leave_family' => (bool) $own && ($project->allows_guests || $user->membershipIn($project)->is_admin),
            'locked'           => $project->lotteryLocked(),
        ]);
    }

    /** Out of their family, staying in the project: where guests are welcome, and for admins */
    public function leave(Request $request, Project $project, FamilyService $families): RedirectResponse
    {
        $families->leave($request->user(), $project);

        return to_route('projects.show', $project)->with('success', __('flash.family_left'));
    }

    /** Someone taking part who has not joined their family here starts it, and is in it */
    public function store(FamilyRequest $request, Project $project, FamilyService $families): RedirectResponse
    {
        $this->authorize('createOwnFamily', $project);

        $user = $request->user();
        $families->start($user, $project, $request->validated());

        $this->notifications->bundle($project, NotificationType::FAMILIES_ADDED, 'families_added', $user, route('families.index', $project));

        return to_route('projects.show', $project)->with('success', __('flash.family_created'));
    }

    /** A family's details open over the list: its address leads there */
    public function show(Project $project, Family $family): RedirectResponse
    {
        return to_route('families.index', ['family' => $family->id]);
    }

    /** For admins, who register and edit families here: the types a family can have */
    private function unitTypesFor(Request $request, Project $project)
    {
        return $request->user()->can('update', $project)
            ? UnitTypeResource::collection($project->unitTypes()->alphabetically()->get())
            : null;
    }
}
