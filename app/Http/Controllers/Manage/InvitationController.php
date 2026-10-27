<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Models\Project;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Inviting people into a project's families, or as admins; pending invitations.
 */
class InvitationController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $invitations = $project->invitations()->pending()
            ->with('family:id,name', 'inviter')
            ->latest()
            ->get();

        return inertia('Manage/Invitations', [
            'invitations' => InvitationResource::collection($invitations),
            'families'    => $project->families()->alphabetically()->get(['id', 'name']),
            'limits'      => $project->isApproved() ? null : [
                'members'             => $project->limit('members'),
                'invitations_per_day' => $project->limit('invitations_per_day'),
            ],
        ]);
    }

    public function store(InvitationRequest $request, Project $project, InvitationService $invitations): RedirectResponse
    {
        $invitations->invite(
            $project,
            $request->user(),
            $request->safe()->only('email', 'firstname', 'lastname'),
            $request->family(),
            $request->asAdmin(),
        );

        return back()->with('success', __('flash.invitation_sent', ['email' => $request->validated('email')]));
    }

    public function destroy(Project $project, Invitation $invitation): RedirectResponse
    {
        $this->authorize('delete', $invitation);

        $invitation->delete();

        return back()->with('success', __('flash.invitation_revoked'));
    }
}
