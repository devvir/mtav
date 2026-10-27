<?php

namespace App\Http\Controllers\Manage;

use App\Enums\LotteryRunStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Inertia\Response;

/**
 * Where an admin starts: what is set up, what is missing, and how the project
 * stands against its limits while unapproved.
 */
class OverviewController extends Controller
{
    public function __invoke(Project $project): Response
    {
        $project->loadCount('unitTypes', 'units', 'families', 'memberships', 'media');
        $run = $project->activeLotteryRun;

        return inertia('Manage/Overview', [
            'counts' => [
                'unit_types'  => $project->unit_types_count,
                'units'       => $project->units_count,
                'families'    => $project->families_count,
                'people'      => $project->memberships_count,
                'invitations' => $project->invitations()->pending()->count(),
                'media'       => $project->media_count,
            ],
            'checklist' => [
                'unit_types' => $project->unit_types_count > 0,
                'units'      => $project->units_count > 0,
                'families'   => $project->families_count > 0,
                'members'    => $project->memberships()->whereNotNull('family_id')->exists(),
                'balanced'   => $project->unitTypes()->withCount('units', 'families')->get()
                    ->every(fn ($type) => $type->units_count === $type->families_count),
                'scheduled' => (bool) $project->lottery->start_date,
                'lottery'   => $run?->status === LotteryRunStatus::COMPLETED,
            ],
            'approval' => [
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
