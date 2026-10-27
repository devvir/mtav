<?php

namespace App\Http\Controllers\Member;

use App\Enums\MediaCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Resources\MediaResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The project's home in the member app: what is coming up and what was shared.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, Project $project): Response
    {
        $user = $request->user();
        $family = $user->familyIn($project);
        $run = $project->activeLotteryRun;

        return inertia('Member/Dashboard', [
            'overview' => [
                'description'    => $project->description,
                'organization'   => $project->organization,
                'families_count' => $project->families()->count(),
                'members_count'  => $project->memberships()->count(),
                'units_count'    => $project->units()->count(),
            ],
            'lottery' => [
                'start_date' => $project->lottery->start_date,
                'status'     => $run?->status->value,
                'unit'       => $family?->unit?->only('id', 'identifier'),
            ],
            'events' => Inertia::defer(fn () => EventResource::collection(
                $project->events()->published()->where('type', '!=', 'lottery')->upcoming()
                    ->with('rsvps')->orderBy('start_date')->limit(3)->get()
            )),
            'media' => Inertia::defer(fn () => MediaResource::collection(
                $project->media()->category(MediaCategory::VISUAL)->with('owner')->latest()->limit(8)->get()
            )),
        ]);
    }
}
