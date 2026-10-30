<?php

namespace App\Services\Lottery;

use App\Models\Family;
use App\Models\LotteryRun;
use App\Models\Unit;
use App\Services\Lottery\Enums\LotteryAuditType;

/**
 * What a lottery run decided from and what it decided, read back from its audit trail
 * (written as it ran, never changed): so that anyone in the project can check that each
 * family received a unit as high in its ranking as the lottery could give it.
 *
 * Each family's place is where its unit stood in its ranking as recorded when the lottery
 * ran. Whole rankings stay with their families: everyone sees places, not preferences.
 */
class RunRecord
{
    public function __construct(private LotteryRun $run)
    {
    }

    /** Whether the run decided anything (a failed one did not) */
    public function exists(): bool
    {
        return $this->audit(LotteryAuditType::INIT) && $this->audit(LotteryAuditType::PROJECT_EXECUTION);
    }

    /**
     * @return array{families: list<array{id: int, name: string, unit: ?array, place: ?int, of: int}>,
     *               worst: ?int, average: ?float, fingerprint: string}
     */
    public function summary(): array
    {
        $rankings = $this->rankings();
        $picks = $this->audit(LotteryAuditType::PROJECT_EXECUTION)['picks'] ?? [];
        $names = Family::whereIn('id', array_keys($rankings))->pluck('name', 'id');
        $units = Unit::whereIn('id', array_values($picks))->pluck('identifier', 'id');

        $families = collect($rankings)->map(function (array $ranking, int $family) use ($picks, $names, $units) {
            $unit = $picks[$family] ?? null;
            $place = $unit === null ? false : array_search($unit, $ranking, true);

            return [
                'id'    => $family,
                'name'  => $names[$family] ?? "#{$family}",
                'unit'  => $unit === null ? null : ['id' => $unit, 'identifier' => $units[$unit] ?? "#{$unit}"],
                'place' => $place === false ? null : $place + 1,
                'of'    => count($ranking),
            ];
        })->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        $places = $families->pluck('place')->filter();

        return [
            'families'    => $families->all(),
            'worst'       => $places->max(),
            'average'     => $places->isEmpty() ? null : round($places->avg(), 2),
            'fingerprint' => $this->fingerprint(),
        ];
    }

    /** A family's whole ranking as the lottery read it: unit ids, most wanted first */
    public function rankingOf(int $family): array
    {
        return $this->rankings()[$family] ?? [];
    }

    /** Everything recorded, as it was written: for admins to keep or hand to an auditor */
    public function full(): array
    {
        return [
            'run' => [
                'uuid'        => $this->run->uuid,
                'project_id'  => $this->run->project_id,
                'status'      => $this->run->status->value,
                'solver'      => $this->run->solver,
                'started_at'  => $this->run->started_at?->toIso8601String(),
                'finished_at' => $this->run->finished_at?->toIso8601String(),
            ],
            'fingerprint' => $this->fingerprint(),
            'trail'       => $this->run->audits()->orderBy('id')->get()
                ->map(fn ($audit) => ['type' => $audit->type->value, 'at' => $audit->created_at, 'record' => $audit->audit])
                ->all(),
        ];
    }

    /**
     * SHA-256 of what the lottery read and what it decided, exactly as stored: the same
     * for as long as the record is untouched
     */
    public function fingerprint(): string
    {
        $raw = fn (LotteryAuditType $type) => $this->run->audits()->where('type', $type)->latest('id')->toBase()->value('audit') ?? '';

        return hash('sha256', $raw(LotteryAuditType::INIT) . "\n" . $raw(LotteryAuditType::PROJECT_EXECUTION));
    }

    /** @return array<int, list<int>> family id => its ranking */
    private function rankings(): array
    {
        // Family ids are the keys: joined, never renumbered
        return array_reduce(
            $this->audit(LotteryAuditType::INIT)['manifest']['data'] ?? [],
            fn (array $all, array $group) => $all + ($group['families'] ?? []),
            [],
        );
    }

    private function audit(LotteryAuditType $type): ?array
    {
        return $this->run->audits()->where('type', $type)->latest('id')->first()?->audit;
    }
}
