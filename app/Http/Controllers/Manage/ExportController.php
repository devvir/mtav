<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A project's lists as spreadsheets (CSV), for its admins: its people, its families and
 * its units, with the headings in the admin's language. Opened by any spreadsheet program
 * (the byte order mark makes Excel read accents right).
 */
class ExportController extends Controller
{
    public function people(Project $project): StreamedResponse
    {
        $families = $project->families()->pluck('name', 'id');

        return $this->csv($project, 'people', [
            __('Name'), __('Email'), __('Phone'), __('ID number'), __('Family'), __('Admin'), __('Joined'),
        ], $project->members()->alphabetically()->get()->map(fn ($person) => [
            $person->name,
            $person->email,
            $person->phone,
            $person->legal_id,
            $families[$person->pivot->family_id] ?? '',
            $person->pivot->is_admin ? __('Yes') : '',
            $person->pivot->created_at?->toDateString(),
        ]));
    }

    public function families(Project $project): StreamedResponse
    {
        return $this->csv(
            $project,
            'families',
            [__('Family'), __('Unit type'), __('People'), __('Unit')],
            $project->families()->with('unitType', 'members', 'unit')->alphabetically()->get()->map(fn ($family) => [
                $family->name,
                $family->unitType?->name,
                $family->members->pluck('name')->join(', '),
                $family->unit?->identifier,
            ])
        );
    }

    public function units(Project $project): StreamedResponse
    {
        return $this->csv(
            $project,
            'units',
            [__('Unit'), __('Unit type'), __('Family')],
            $project->units()->with('type', 'family')->alphabetically()->get()->map(fn ($unit) => [
                $unit->identifier,
                $unit->type?->name,
                $unit->family?->name,
            ])
        );
    }

    /** @param iterable<array<int, mixed>> $rows */
    private function csv(Project $project, string $what, array $headings, iterable $rows): StreamedResponse
    {
        $name = str($project->name)->slug() . "-{$what}-" . now()->toDateString() . '.csv';

        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            fputcsv($out, $headings, escape: '');
            foreach ($rows as $row) {
                fputcsv($out, $row, escape: '');
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
