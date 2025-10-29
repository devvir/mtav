<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateMemberRequest;
use App\Http\Requests\DeleteMemberRequest;
use App\Http\Requests\IndexMembersRequest;
use App\Http\Requests\RestoreMemberRequest;
use App\Http\Requests\ShowCreateMemberRequest;
use App\Http\Requests\EditMemberRequest;
use App\Http\Requests\ShowMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexMembersRequest $request): Response
    {
        setState('groupMembers', false);

        $members = Member::alphabetically()
            ->with('family')
            ->when($request->project_id, fn ($q, $projectId) => $q->inProject($projectId))
            ->when($request->q, fn ($query, $q) => $query->search($q, searchFamily: true));

        return inertia('Members/Index', [
            'members' => Inertia::deepMerge(fn () => $members->paginate(30)),
            'q' => $request->q ?? '',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ShowMemberRequest $_, Member $member): Response
    {
        $member->load('family');

        return inertia('Members/Show', compact('member'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ShowCreateMemberRequest $request): Response
    {
        $families = Family::alphabetically()
            ->when($request->project_id, fn ($q, $projectId) => $q->where('project_id', $projectId));

        $projectsPool = $request->user()->isSuperadmin()
            ? Project::query()
            : $request->user()->projects();

        return inertia('Members/Create', [
            'families' => $families->get(),
            'projects' => $projectsPool->alphabetically()->get(),
        ]);
    }    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateMemberRequest $request): RedirectResponse
    {
        $member = DB::transaction(function () use ($request) {
            return Member::create([
                ...$request->only(['firstname', 'lastname', 'email']),
                'family_id' => $request->family ?: null,
                'password' => random_bytes(16),
            ])->joinProject($request->project_id);
        });

        event(new Registered($member));

        return to_route('members.show', $member->id)
            ->with('success', __('Member created successfully.'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EditMemberRequest $_, Member $member): Response
    {
        $member->load('family', 'projects');

        return inertia('Members/Edit', compact('member'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $member->update($request->all());

        return redirect()->back()
            ->with('success', __('Member updated successfully.'));
    }

    /**
     * Delete the resource.
     */
    public function destroy(DeleteMemberRequest $request, Member $member): RedirectResponse
    {
        $member->delete();

        return to_route('members.index')
            ->with('success', __('Member deleted successfully.'));
    }

    /**
     * Restore the soft-deleted resource.
     */
    public function restore(RestoreMemberRequest $_, Member $member): RedirectResponse
    {
        $member->restore();

        return to_route('members.show', $member->id)
            ->with('success', __('Member restored successfully.'));
    }
}
