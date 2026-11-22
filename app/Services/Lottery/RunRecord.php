<?php

namespace App\Services\Lottery;

use App\Enums\LotteryRunStatus;
use App\Models\Activity;
use App\Models\Family;
use App\Models\LotteryRun;
use App\Models\Unit;
use App\Models\UnitType;
use App\Services\Lottery\Enums\LotteryAuditType;
use App\Services\Lottery\Solvers\Glpk\ModelGenerator;
use Illuminate\Support\Collection;

/**
 * What a lottery run decided from and what it decided, read back from its audit trail
 * (written as it ran, never changed): so that anyone in the project can check that each
 * family received a unit as high in its ranking as the lottery could give it.
 *
 * Each family's place is where its unit stood in its ranking as recorded when the lottery
 * ran. Rankings stay with their families until the run is **disclosed**: completed, and past
 * the hours the platform gives admins to invalidate it (PlatformSetting::DISCLOSURE_HOURS).
 * Then every ranking is everyone's to see, so that anyone can check the result, and the
 * whole record is everyone's to download. Never anyone's email or phone: the record names
 * people, and that is all it needs.
 */
class RunRecord
{
    public function __construct(private LotteryRun $run)
    {
    }

    /** Whether the run decided anything to show (a failed one did not, even if the solver answered; a running one's result is held) */
    public function exists(): bool
    {
        return in_array($this->run->status, [LotteryRunStatus::COMPLETED, LotteryRunStatus::INVALIDATED], true) && $this->audit(LotteryAuditType::INIT) && $this->audit(LotteryAuditType::PROJECT_EXECUTION);
    }

    /** Whether every family's ranking, and the whole record, are everyone's to see */
    public function disclosed(): bool
    {
        return $this->run->disclosed();
    }

    /**
     * @return array{families: list<array{id: int, name: string, unit: ?array, place: ?int, of: int, ranking: ?list<string>}>,
     *               worst: ?int, average: ?float, fingerprint: string}
     */
    public function summary(): array
    {
        $rankings = $this->rankings();
        $picks = $this->audit(LotteryAuditType::PROJECT_EXECUTION)['picks'] ?? [];
        $names = Family::withTrashed()->whereIn('id', array_keys($rankings))->pluck('name', 'id');
        $disclosed = $this->disclosed();
        $units = $this->unitNames($disclosed ? collect($rankings)->flatten()->merge($picks) : collect($picks));

        $families = collect($rankings)->map(function (array $ranking, int $family) use ($picks, $names, $units, $disclosed) {
            $unit = $picks[$family] ?? null;
            $place = $unit === null ? false : array_search($unit, $ranking, true);

            return [
                'id'      => $family,
                'name'    => $names[$family] ?? __('lottery.family_gone', ['id' => $family]),
                'unit'    => $unit === null ? null : ['id' => $unit, 'identifier' => $units[$unit] ?? "#{$unit}"],
                'place'   => $place === false ? null : $place + 1,
                'of'      => count($ranking),
                'ranking' => $disclosed ? array_map(fn (int $id) => $units[$id] ?? "#{$id}", $ranking) : null,
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

    /** Units named: id => identifier, for those the record speaks of */
    public function unitNames(Collection $ids): Collection
    {
        return Unit::whereIn('id', $ids->unique())->pluck('identifier', 'id');
    }

    /**
     * Everything recorded, as it was written, but for people's addresses: for anyone to keep
     * or hand to an auditor
     */
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
                ->map(fn ($audit) => ['type' => $audit->type->value, 'at' => $audit->created_at, 'record' => self::withoutAddresses($audit->audit)])
                ->all(),
        ];
    }

    /**
     * The input as the lottery read it, in the project's words: each unit type with its
     * units and its families, each family with its ranking, most wanted first
     *
     * @return list<array{unit_type: string, units: list<string>, families: list<array{id: int, name: string, ranking: list<string>}>}>
     */
    public function input(): array
    {
        $manifest = $this->audit(LotteryAuditType::INIT)['manifest']['data'] ?? [];
        $units = $this->unitNames(collect($manifest)->map(fn (array $group) => $group['items'] ?? $group['units'] ?? [])->flatten());
        $types = UnitType::whereIn('id', array_keys($manifest))->pluck('name', 'id');
        $names = Family::withTrashed()->whereIn('id', array_keys($this->rankings()))->pluck('name', 'id');
        $name = fn (int $id) => $units[$id] ?? "#{$id}";

        return collect($manifest)->map(fn (array $group, $type) => [
            'unit_type' => $types[$type] ?? "#{$type}",
            'units'     => array_map($name, $group['items'] ?? $group['units'] ?? []),
            'families'  => collect($group['participants'] ?? $group['families'] ?? [])
                ->map(fn (array $ranking, int $family) => [
                    'id'      => $family,
                    'name'    => $names[$family] ?? __('lottery.family_gone', ['id' => $family]),
                    'ranking' => array_map($name, $ranking),
                ])->values()->all(),
        ])->values()->all();
    }

    /**
     * How each family's ranking changed before the run, newest last: who changed it (by name),
     * when they began and when they stopped, and the ranking they left, most wanted first
     *
     * @return list<array{family: string, by: ?string, from: string, until: string, ranking: list<string>}>
     */
    public function changes(): array
    {
        $families = array_keys($this->rankings());
        $changes = Activity::where('action', 'ranked')
            ->where('subject_type', 'family')
            ->whereIn('subject_id', $families)
            ->where('created_at', '<=', $this->run->started_at)
            ->orderBy('created_at')
            ->get();
        $names = Family::withTrashed()->whereIn('id', $families)->pluck('name', 'id');
        $units = $this->unitNames($changes->map(fn (Activity $change) => $change->changes['ranking'][1] ?? [])->flatten());

        return $changes->map(fn (Activity $change) => [
            'family'  => $names[$change->subject_id] ?? __('lottery.family_gone', ['id' => $change->subject_id]),
            'by'      => $change->actor,
            'from'    => $change->created_at->toIso8601String(),
            'until'   => $change->changes['until'] ?? $change->created_at->toIso8601String(),
            'ranking' => array_map(fn (int $id) => $units[$id] ?? "#{$id}", $change->changes['ranking'][1] ?? []),
        ])->all();
    }

    /** The GLPK models the lottery is solved with, by file name: the same for every run */
    public static function models(): array
    {
        $generator = new ModelGenerator();

        return [
            'phase1-max-min.mod'      => $generator->generatePhase1Model(),
            'phase2-satisfaction.mod' => $generator->generatePhase2Model(),
            'item-selection.mod'      => $generator->generateItemSelectionModel(),
        ];
    }

    /**
     * Each resolution of the solver, in order: its task and status, what it found, and the
     * files it exchanged with GLPK (the model, the data and the solution), by file name
     *
     * @return list<array{task: ?string, status: ?string, result: mixed, metadata: array, artifacts: array<string, string>}>
     */
    public function solves(): array
    {
        return $this->run->audits()->where('type', LotteryAuditType::CUSTOM)->orderBy('id')->get()
            ->map(function ($audit) {
                $record = $audit->audit;
                $metadata = $record['metadata'] ?? [];
                $artifacts = $metadata['artifacts'] ?? [];
                unset($metadata['artifacts']);

                return [
                    'task'      => $record['task'] ?? null,
                    'status'    => $record['status'] ?? null,
                    'result'    => $record['result'] ?? null,
                    'metadata'  => $metadata,
                    'artifacts' => is_array($artifacts) ? $artifacts : [],
                ];
            })->all();
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

    /** A record with every email address taken out: a run recorded before admins were named by name alone carries one */
    public static function withoutAddresses(array $record): array
    {
        foreach ($record as $key => $value) {
            if ($key === 'email') {
                unset($record[$key]);
            } elseif (is_array($value)) {
                $record[$key] = self::withoutAddresses($value);
            }
        }

        return $record;
    }

    /** @return array<int, list<int>> family id => its ranking */
    private function rankings(): array
    {
        // The manifest's participants are families, its items units. Family ids are the keys:
        // joined, never renumbered. Runs recorded before the lottery spoke of participants
        // named them families, and their record is read as it was written
        return array_reduce(
            $this->audit(LotteryAuditType::INIT)['manifest']['data'] ?? [],
            fn (array $all, array $group) => $all + ($group['participants'] ?? $group['families'] ?? []),
            [],
        );
    }

    private function audit(LotteryAuditType $type): ?array
    {
        return $this->run->audits()->where('type', $type)->latest('id')->first()?->audit;
    }
}
