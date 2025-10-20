<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        updateState('groupMembers', false);

        $pool = Project::current()?->members() ?? Member::query();

        $members = $pool->alphabetically()->with('family')
            ->when($request->q, fn ($query, $q) => $query->search($q, searchFamily: true));

        return inertia('Members/Index', [
            'members' => Inertia::deepMerge(fn () => $members->paginate(30)),
            'q' => $request->string('q', ''),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Member $member): Response
    {
        $member->load('family');

        return inertia('Members/Show', compact('member'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $familiesPool = Project::current()?->families() ?? Family::query();

        $projectsPool = $request->user()->isSuperAdmin()
            ? Project::query()
            : $request->user()->projects();

        return inertia('Members/Create', [
            'families' => $familiesPool->alphabetically()->get(),
            'projects' => $projectsPool->alphabetically()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateMemberRequest $request): RedirectResponse
    {
        $member = DB::transaction(function () use ($request) {
            return Member::create([
                ...$request->only(['firstname', 'lastname', 'email']),
                'family_id' => $request->family ?: null,
                'password' => bcrypt(random_bytes(16)),
            ])->joinProject($request->project);
        });

        event(new Registered($member));

        return to_route('members.show', $member->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Member $member): Response
    {
        $member->load('family', 'projects');

        return inertia('Members/Edit', compact('member'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $member->update($request->validated());

        return redirect()->back();
    }

    /**
     * Delete the resource.
     */
    public function destroy(Member $member): RedirectResponse
    {
        $member->delete();

        return to_route('members.index');
    }
}
