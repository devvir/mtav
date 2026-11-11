<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Inertia\Response;

/**
 * What people told the platform's team through the Assistant: by person, whoever wrote most
 * lately first, each with their reports, latest first
 */
class ReportController extends Controller
{
    public function index(): Response
    {
        $people = User::whereHas('reports')
            ->withMax('reports', 'created_at')
            ->orderByDesc('reports_max_created_at')
            ->with(['reports' => fn ($query) => $query->latest()->with('project:id,name')])
            ->paginate(config('lists.per_page.reports'), ['id', 'firstname', 'lastname', 'email']);

        return inertia('Platform/Reports', [
            'people' => $people->through(fn (User $person) => [
                'id'      => $person->id,
                'name'    => $person->name,
                'email'   => $person->email,
                'reports' => $person->reports->map($this->shown(...)),
            ]),
            // Of accounts since deleted: what they said stays, by nobody
            'unsigned' => Report::whereNull('user_id')->latest()->with('project:id,name')->limit(50)->get()->map($this->shown(...)),
        ]);
    }

    /** @return array{id: int, body: string, project: ?string, at: mixed} */
    private function shown(Report $report): array
    {
        return ['id' => $report->id, 'body' => $report->body, 'project' => $report->project?->name, 'at' => $report->created_at];
    }
}
