<?php

namespace App\Services;

use App\Http\Resources\FamilyResource;
use App\Http\Resources\MemberResource;
use App\Models\Project;
use App\Models\User;

/**
 * One search over a project's people, whichever of its pages it starts from: the families, the
 * members and the admins it finds, each kind apart (someone who is both shows in both). Found
 * by the same words each list finds them by, and each kind is capped: a search narrows.
 */
class PeopleSearch
{
    public const LIMIT = 30;

    /** @return array{families: mixed, members: mixed, admins: mixed} */
    public function find(Project $project, User $user, string $q): array
    {
        $byEmail = $user->can('update', $project);
        $nameOnly = $user->isGuestIn($project);

        $families = $project->families()
            ->with('unitType:id,name', 'unit:id,family_id,identifier', 'members')
            ->withCount('members')
            ->search($q, byEmail: $byEmail, nameOnly: $nameOnly)
            ->alphabetically()
            ->limit(self::LIMIT)
            ->get();

        $people = fn () => $project->members()
            ->with(['memberships' => fn ($query) => $query->where('project_id', $project->id)->with('family:id,name')])
            ->search($q, byEmail: $byEmail, nameOnly: $nameOnly)
            ->alphabetically()
            ->limit(self::LIMIT);

        return [
            'families' => FamilyResource::collection($families),
            'members'  => MemberResource::collection($people()->get()),
            'admins'   => MemberResource::collection($people()->where('memberships.is_admin', true)->get()),
        ];
    }
}
