<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inertia\Response;

/**
 * What happened on the platform, newest first, for superadmins: every change and every
 * way in, by person, project or kind
 */
class ActivityController extends Controller
{
    /** The kinds of things to look at, and the actions each takes in */
    private const KINDS = [
        'changes'  => ['created', 'updated', 'deleted'],
        'sign_ins' => ['signed_in', 'signed_out', 'sign_in_failed', 'password_reset'],
    ];

    public function index(Request $request): Response
    {
        $kind = $request->string('kind')->toString();
        $activity = Activity::with('project:id,name')
            ->when($request->q, fn ($q, $search) => $q->search($search))
            ->when($request->integer('project'), fn ($q, $project) => $q->where('project_id', $project))
            ->when(self::KINDS[$kind] ?? null, fn ($q, $actions) => $q->whereIn('action', $actions))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return inertia('Platform/Activity', [
            'activity' => JsonResource::collection($activity->through(fn (Activity $entry) => [
                'id'           => $entry->id,
                'at'           => $entry->created_at,
                'actor'        => $entry->actor,
                'action'       => $entry->action,
                'subject_type' => $entry->subject_type,
                'subject'      => $entry->subject,
                'project'      => $entry->project?->name,
                'changes'      => $entry->changes,
                'ip'           => $entry->ip,
            ])),
            'projects' => Project::withTrashed()->orderBy('name')->get(['id', 'name']),
            'filters'  => [
                'q'       => $request->q,
                'project' => $request->integer('project') ?: null,
                'kind'    => array_key_exists($kind, self::KINDS) ? $kind : null,
            ],
        ]);
    }
}
