<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ProjectService;
use Inertia\Response;

/**
 * Where an admin starts: what is set up, what is missing, and how the project
 * stands against its limits while unapproved.
 */
class OverviewController extends Controller
{
    public function __invoke(Project $project, ProjectService $projects): Response
    {
        $project->loadCount('unitTypes', 'units', 'families', 'memberships', 'media');

        return inertia('Manage/Overview', [
            'counts' => [
                'unit_types'  => $project->unit_types_count,
                'units'       => $project->units_count,
                'families'    => $project->families_count,
                'people'      => $project->memberships_count,
                'invitations' => $project->invitations()->pending()->count(),
                'media'       => $project->media_count,
            ],
            'checklist' => $projects->setupChecklist($project),
            'approval'  => [
                'approved'     => $project->isApproved(),
                'requested_at' => $project->approval_requested_at,
                'limits'       => $project->isApproved() ? null : config('limits.unapproved'),
                'usage'        => $project->isApproved() ? null : [
                    'members'          => $project->memberships_count,
                    'units'            => $project->units_count,
                    'families'         => $project->families_count,
                    'media_storage_mb' => round($project->media()->sum('file_size') / 1024 / 1024, 1),
                ],
            ],
        ]);
    }
}
