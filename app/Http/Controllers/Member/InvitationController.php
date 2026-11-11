<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnFamilyInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Models\Project;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Members bring the rest of their family into the project.
 */
class InvitationController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $family = $request->user()->familyIn($project);

        abort_unless($family, 404);

        return inertia('Member/Invitations/Index', [
            'family'      => ['id' => $family->id, 'name' => $family->name],
            'invitations' => InvitationResource::collection(
                $project->invitations()->pending()->where('family_id', $family->id)->with('inviter')->latest()->get()
            ),
        ]);
    }

    public function store(OwnFamilyInvitationRequest $request, Project $project, InvitationService $invitations): RedirectResponse
    {
        $result = $invitations->invite($project, $request->user(), $request->validated(), $request->family(), asAdmin: false);

        return back()->with('success', $invitations->outcome($result));
    }

    public function destroy(Project $project, Invitation $invitation): RedirectResponse
    {
        $this->authorize('delete', $invitation);

        $invitation->delete();

        return back()->with('success', __('flash.invitation_revoked'));
    }
}
