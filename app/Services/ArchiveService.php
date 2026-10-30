<?php

namespace App\Services;

use App\Enums\MediaCategory;
use App\Models\Event;
use App\Models\Media;
use App\Models\Project;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * What was removed from a project and kept: for its admins, a switch on each page shows it,
 * to restore any of it.
 */
class ArchiveService
{
    /**
     * For a page: how many it has archived, and (?archived=1) which
     *
     * @return array{count: int, shown: bool, items: ?array}|null  null for those who are no admins
     */
    public function forPage(Request $request, Project $project, string $kind): ?array
    {
        if (! $request->user()->can('update', $project)) {
            return null;
        }

        $query = $this->query($project, $kind);
        $shown = $request->boolean('archived');

        return [
            'count' => (clone $query)->count(),
            'shown' => $shown,
            'items' => $shown ? (clone $query)->latest('deleted_at')->limit(200)->get()
                ->map(fn ($item) => [
                    'id'         => $item->id,
                    'title'      => $item instanceof Media ? ($item->title ?: $item->original_name) : $item->title,
                    'deleted_at' => $item->deleted_at,
                ]) : null,
        ];
    }

    public function restore(Project $project, string $kind, int $id): void
    {
        $this->query($project, $kind)->findOrFail($id)->restore();
    }

    private function query(Project $project, string $kind): Builder
    {
        return match ($kind) {
            'events'  => Event::onlyTrashed()->where('project_id', $project->id),
            'gallery' => Media::onlyTrashed()->where('project_id', $project->id)->category(MediaCategory::MEDIA),
            'files'   => Media::onlyTrashed()->where('project_id', $project->id)->category(MediaCategory::FILES),
            'forum'   => Topic::onlyTrashed()->where('project_id', $project->id),
            default   => abort(404),
        };
    }
}
