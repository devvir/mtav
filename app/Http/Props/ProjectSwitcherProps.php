<?php

namespace App\Http\Props;

use App\Models\Project;
use App\Models\User;

/**
 * The projects someone moves between (the project switcher, and the frame of their own
 * pages): those they take part in. A superadmin reaches every project on the platform, so
 * they get a few, with the one browsed and the one last browsed among them; all of them
 * are on the projects page.
 */
class ProjectSwitcherProps
{
    /** How many a superadmin's switcher lists, besides the ones browsed */
    public const SUPERADMIN_SHOWN = 10;

    private const COLUMNS = ['id', 'name', 'organization', 'feature_choices'];

    /** @return list<array<string, mixed>> */
    public function of(User $user, ?Project $current, ?int $lastBrowsed): array
    {
        $managed = $user->memberships->where('is_admin', true)->pluck('project_id');

        return $this->projects($user, $current, $lastBrowsed)
            ->map(fn (Project $project) => [
                'id'           => $project->id,
                'name'         => $project->name,
                'organization' => $project->organization,
                'features'     => $project->features(),
                'is_admin'     => $user->isSuperadmin() || $managed->contains($project->id),
            ])
            ->values()
            ->all();
    }

    /** @return \Illuminate\Support\Collection<int, Project> */
    private function projects(User $user, ?Project $current, ?int $lastBrowsed)
    {
        if (! $user->isSuperadmin()) {
            return $user->reachableProjects()->orderBy('name')->get(self::COLUMNS);
        }

        $shown = $user->reachableProjects()->orderBy('name')->limit(self::SUPERADMIN_SHOWN)->pluck('id')
            ->merge(array_filter([$current?->id, $lastBrowsed]));

        return $user->reachableProjects()->whereKey($shown->unique()->all())->orderBy('name')->get(self::COLUMNS);
    }
}
