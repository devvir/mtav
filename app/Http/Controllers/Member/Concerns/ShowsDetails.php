<?php

namespace App\Http\Controllers\Member\Concerns;

use App\Http\Resources\FamilyResource;
use App\Http\Resources\MemberResource;
use App\Http\Resources\PlanResource;
use App\Http\Resources\UnitResource;
use App\Models\FamilyJoinRequest;
use App\Models\Project;
use Illuminate\Http\Request;

/**
 * A family's, a person's or a unit's details, shown over whichever page asks for them by its
 * address (?family=12, ?person=19, ?unit=7): the same wherever their card is.
 */
trait ShowsDetails
{
    protected function familyShown(Request $request, Project $project): ?FamilyResource
    {
        $family = $request->integer('family') ? $project->families()->find($request->integer('family')) : null;

        return $family ? FamilyResource::make($family->load('unitType', 'unit', 'members')) : null;
    }

    /** For those in the family shown, who answer them: who asks to join, and who was declined */
    protected function requestsShown(Request $request, Project $project): ?array
    {
        $family = $request->integer('family') ? $project->families()->find($request->integer('family')) : null;

        if (! $family || ! $request->user()->can('answerRequests', $family)) {
            return null;
        }

        return $family->joinRequests()->with('user:id,firstname,lastname')->oldest()->get()
            ->map(fn (FamilyJoinRequest $joinRequest) => [
                'id'       => $joinRequest->id,
                'name'     => $joinRequest->user->name,
                'declined' => (bool) $joinRequest->declined_at,
            ])->all();
    }

    /** @return array{person: MemberResource, families: mixed}|null */
    protected function personShown(Request $request, Project $project): ?array
    {
        $member = $request->integer('person')
            ? $project->members()->withMembershipIn($project)->find($request->integer('person'))
            : null;

        return $member ? [
            'person' => MemberResource::make($member),
            // For admins, who change people's role and family here
            'families' => $request->user()->can('update', $project)
                ? $project->familyChoices()
                : null,
        ] : null;
    }

    protected function unitShown(Request $request, Project $project): ?UnitResource
    {
        $unit = $request->integer('unit') ? $project->units()->find($request->integer('unit')) : null;

        return $unit ? UnitResource::make($unit->load('type', 'family')) : null;
    }

    /** The plan a unit's details show it on, only when a unit is asked for */
    protected function planShown(Request $request, Project $project): ?PlanResource
    {
        return $request->integer('unit') && $project->plan ? PlanResource::make($project->plan->load('items.unit.family')) : null;
    }
}
