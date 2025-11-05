<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateMemberRequest;
use App\Http\Requests\FilteredIndexRequest;
use App\Http\Requests\ProjectScopedRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(FilteredIndexRequest $request): Response
    {
        defineState('groupMembers', false);

        $members = Member::alphabetically()
            ->with('family')
            ->when($request->project_id, fn ($q, int $id) => $q->inProject($id))
            ->when($request->q, fn ($q, $search) => $q->search($search, searchFamily: true));

        return inertia('Members/Index', [
            'members' => Inertia::deepMerge(fn () => $members->paginate(30)),
            'q' => $request->q ?? '',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Member $member): Response
    {
        $member->load('family', 'projects');

        return inertia('Members/Show', compact('member'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ProjectScopedRequest $request): Response
    {
        $families = Family::when($request->project_id, fn ($q, $id) => $q->inProject($id));

        return inertia('Members/Create', [
            'families' => $families->alphabetically()->get(),
            'projects' => Project::alphabetically()->get(),
        ]);
    }

    /**
     * Create (Invite) a new Member.
     */
    public function store(
        CreateMemberRequest $request,
        InvitationService $invitationService
    ): RedirectResponse {
        $member = $invitationService->inviteMember(
            $request->except('project_id'),
            $request->project_id
        );

        return to_route('members.show', $member->id)
            ->with('success', __('Member invitation sent!'));
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

        return redirect()->back()
            ->with('success', __('Member information updated!'));
    }

    /**
     * Delete the resource.
     */
    public function destroy(Member $member): RedirectResponse
    {
        $member->delete();

        return to_route('members.index')
            ->with('success', __('Member deleted!'));
    }

    /**
     * Restore the soft-deleted resource.
     */
    public function restore(Member $member): RedirectResponse
    {
        $member->restore();

        return to_route('members.show', $member->id)
            ->with('success', __('Member restored!'));
    }
}
