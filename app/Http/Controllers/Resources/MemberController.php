<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateMemberRequest;
use App\Http\Requests\FilteredIndexRequest;
use App\Http\Requests\ProjectScopedRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use App\Services\Form\FormService;
use App\Services\Form\FormType;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(FilteredIndexRequest $request): Response
    {
        defineState('groupMembers', false);

        $members = Member::alphabetically()
            ->with('family')
            ->when($request->project_id, fn ($q, int $id) => $q->inProject($id))
            ->when($request->q, fn ($q, $search) => $q->search($search, searchFamily: true));

        return inertia('Members/Index', [
            'members' => Inertia::defer(fn () => $members->paginate(30))->deepMerge(),
            'q'       => $request->q ?? '',
        ]);
    }

    public function show(Member $member): Response
    {
        $member->load('family', 'projects');

        return inertia('Members/Show', compact('member'));
    }

    public function create(ProjectScopedRequest $request): Response
    {
        $formSpecs = FormService::make(Member::class, FormType::CREATE);

        return inertia('Members/Create', [
            'form' => $formSpecs,
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

        return to_route('members.show', $member)
            ->with('success', __('flash.member_invitation_sent'));
    }

    public function edit(Member $member): Response
    {
        $formSpecs = FormService::make($member, FormType::UPDATE);

        return inertia('Members/Edit', [
            'form' => $formSpecs,
        ]);
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $member->update($request->validated());

        return back()
            ->with('success', __('flash.member_updated'));
    }

    public function destroy(Member $member): RedirectResponse
    {
        $member->delete();

        return to_route('members.index')
            ->with('success', __('flash.member_deleted'));
    }

    public function restore(Member $member): RedirectResponse
    {
        $member->restore();

        return to_route('members.show', $member)
            ->with('success', __('flash.member_restored'));
    }
}
