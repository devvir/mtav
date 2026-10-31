<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyJoinRequest;
use App\Models\Project;
use App\Services\FamilyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Asking to join a family, and its members answering.
 */
class FamilyJoinRequestController extends Controller
{
    public function __construct(private FamilyService $families)
    {
    }

    public function store(Request $request, Project $project, Family $family): RedirectResponse
    {
        $this->authorize('askToJoin', $family);

        $this->families->ask($family, $request->user());

        return back()->with('success', __('flash.family_asked', ['family' => $family->name]));
    }

    /** They changed their mind */
    public function destroy(Request $request, Project $project, FamilyJoinRequest $familyJoinRequest): RedirectResponse
    {
        abort_unless($familyJoinRequest->user_id === $request->user()->id && ! $familyJoinRequest->declined_at, 403);

        $familyJoinRequest->delete();

        return back()->with('success', __('flash.family_ask_cancelled'));
    }

    public function accept(Request $request, Project $project, FamilyJoinRequest $familyJoinRequest): RedirectResponse
    {
        $this->authorize('answerRequests', $familyJoinRequest->family);
        abort_if($familyJoinRequest->declined_at, 403);

        $this->families->accept($familyJoinRequest, $request->user());

        return back()->with('success', __('flash.family_ask_accepted', ['name' => $familyJoinRequest->user->name]));
    }

    public function decline(Request $request, Project $project, FamilyJoinRequest $familyJoinRequest): RedirectResponse
    {
        $this->authorize('answerRequests', $familyJoinRequest->family);

        $this->families->decline($familyJoinRequest, $request->user());

        return back()->with('success', __('flash.family_ask_declined', ['name' => $familyJoinRequest->user->name]));
    }

    /** A decline made by mistake: they may ask again */
    public function undecline(Request $request, Project $project, FamilyJoinRequest $familyJoinRequest): RedirectResponse
    {
        $this->authorize('answerRequests', $familyJoinRequest->family);
        abort_unless($familyJoinRequest->declined_at, 403);

        $familyJoinRequest->delete();

        return back()->with('success', __('flash.family_ask_undeclined', ['name' => $familyJoinRequest->user->name]));
    }
}
