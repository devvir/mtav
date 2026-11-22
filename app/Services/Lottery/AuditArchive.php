<?php

namespace App\Services\Lottery;

use App\Models\LotteryRun;
use RuntimeException;
use ZipArchive;

/**
 * A run's whole record as one file to download: everything an auditor needs to follow the
 * lottery from the rankings to the result, in files people and programs read. Built on
 * demand from the audit trail; nothing in it is anyone's email or phone.
 */
class AuditArchive
{
    public function __construct(private LotteryRun $run)
    {
    }

    /** The archive, written to a file of its own (the caller sends it and removes it) */
    public function write(): string
    {
        $record = new RunRecord($this->run);
        $path = tempnam(sys_get_temp_dir(), 'mtav-audit-');
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not write the archive at {$path}");
        }

        $json = fn (mixed $data) => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        $summary = $record->summary();

        $zip->addFromString('README.txt', $this->readme($record, $summary['fingerprint']));
        $zip->addFromString('record.json', $json($record->full()));
        $zip->addFromString('input.json', $json($record->input()));
        $zip->addFromString('result.csv', $this->csv(
            [__('lottery.audit_family'), __('lottery.audit_unit'), __('lottery.audit_place'), __('lottery.audit_of')],
            array_map(fn (array $family) => [$family['name'], $family['unit']['identifier'] ?? '', $family['place'] ?? '', $family['of']], $summary['families']),
        ));
        $zip->addFromString('ranking-changes.csv', $this->csv(
            [__('lottery.audit_family'), __('lottery.audit_by'), __('lottery.audit_from'), __('lottery.audit_until'), __('lottery.audit_ranking')],
            array_map(fn (array $change) => [$change['family'], $change['by'] ?? '', $change['from'], $change['until'], implode(' > ', $change['ranking'])], $record->changes()),
        ));

        foreach (RunRecord::models() as $name => $model) {
            $zip->addFromString("models/{$name}", $model);
        }

        foreach ($record->solves() as $i => $solve) {
            $folder = sprintf('solves/%02d', $i + 1);
            $zip->addFromString("{$folder}/solve.json", $json(collect($solve)->except('artifacts')->all()));

            foreach ($solve['artifacts'] as $name => $content) {
                $zip->addFromString("{$folder}/" . basename((string) $name), (string) $content);
            }
        }

        $zip->close();

        return $path;
    }

    /** What is inside and how to check it, in the language of whoever downloads it */
    private function readme(RunRecord $record, string $fingerprint): string
    {
        $run = $this->run;

        return implode("\n", [
            __('lottery.audit_readme_title', ['project' => $run->project?->name ?? "#{$run->project_id}"]),
            '',
            __('lottery.audit_readme_run', ['uuid' => $run->uuid, 'date' => $run->finished_at?->toDayDateTimeString() ?? $run->started_at?->toDayDateTimeString()]),
            __('lottery.audit_readme_fingerprint', ['fingerprint' => $fingerprint]),
            '',
            __('lottery.audit_readme_files'),
            '',
            __('lottery.audit_readme_how'),
            '',
            __('lottery.audit_readme_privacy'),
        ]) . "\n";
    }

    /** @param list<list<mixed>> $rows */
    private function csv(array $headings, array $rows): string
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, $headings, escape: '');
        foreach ($rows as $row) {
            fputcsv($out, $row, escape: '');
        }
        rewind($out);

        return stream_get_contents($out);
    }
}
