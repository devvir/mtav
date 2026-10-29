<?php

namespace App\Services\Delivery;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * What GitHub knows of the deliveries, and asking it to run one: each environment's
 * workflow deploys (the console never reaches a host itself).
 */
class GitHub
{
    /** Reads are kept this long: a page reloaded often asks GitHub once */
    private const FRESH_SECONDS = 30;

    /** What each kind of request runs */
    public const WORKFLOWS = ['dev' => 'dev.yml', 'production' => 'production.yml', 'rollback' => 'rollback.yml'];

    public static function configured(): bool
    {
        return filled(config('delivery.github.token'));
    }

    /**
     * What each environment runs: its release, when it went live and how, and how far
     * behind main it is.
     *
     * @return array<int, array<string, mixed>>
     */
    public function environments(): array
    {
        return Cache::remember('delivery.environments', self::FRESH_SECONDS, function () {
            $main = $this->main();
            $runs = $this->runs()->keyBy('id');

            return collect(config('delivery.environments'))->map(function ($url, $name) use ($main, $runs) {
                $live = $this->live($name, $runs);

                return [
                    'name'   => $name,
                    'url'    => $url,
                    'live'   => $live,
                    'behind' => $live ? $this->behind($live['sha'], $main['sha']) : null,
                ];
            })->values()->all();
        });
    }

    /** The latest of main */
    public function main(): array
    {
        return Cache::remember('delivery.main', self::FRESH_SECONDS, fn () => $this->commit($this->get('commits/main')));
    }

    /** @return list<string> */
    public function branches(): array
    {
        return Cache::remember('delivery.branches', self::FRESH_SECONDS, fn () => collect($this->get('branches', ['per_page' => 100]))
            ->pluck('name')->sort()->values()->all());
    }

    /**
     * The latest runs of the delivery workflows (the pipeline's included: it deploys
     * staging).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function runs(): Collection
    {
        return collect(Cache::remember('delivery.runs', self::FRESH_SECONDS, fn () => $this->get('actions/runs', ['per_page' => 30])['workflow_runs'] ?? []))
            ->filter(fn ($run) => in_array(basename($run['path'] ?? ''), [...array_values(self::WORKFLOWS), 'pipeline.yml'], true))
            ->map(fn ($run) => [
                'id'         => $run['id'],
                'workflow'   => basename($run['path']),
                'title'      => $run['display_title'] ?? $run['name'],
                'branch'     => $run['head_branch'],
                'sha'        => substr($run['head_sha'], 0, 7),
                'status'     => $run['status'],
                'conclusion' => $run['conclusion'],
                'url'        => $run['html_url'],
                'started_at' => $run['run_started_at'] ?? $run['created_at'],
                'by'         => $run['triggering_actor']['login'] ?? null,
            ])
            ->values();
    }

    /**
     * Ask GitHub to run a delivery workflow, on a branch, with its inputs.
     *
     * @param array<string, string|bool> $inputs
     */
    public function dispatch(string $workflow, string $ref, array $inputs = []): void
    {
        $this->client()->post("actions/workflows/{$workflow}/dispatches", [
            'ref'    => $ref,
            'inputs' => array_map(fn ($value) => is_bool($value) ? ($value ? 'true' : 'false') : $value, $inputs),
        ])->throw();

        Cache::forget('delivery.runs');
        Cache::forget('delivery.environments');
        Cache::forget('delivery.main');
    }

    /**
     * The release an environment runs: its history replayed, oldest first. A deploy goes
     * live and keeps the release it replaces idle beside it (blue and green); a rollback
     * swaps the two.
     */
    private function live(string $environment, Collection $runs): ?array
    {
        $history = [];
        foreach ($this->get('deployments', ['environment' => $environment, 'per_page' => 10]) as $deployment) {
            $status = $this->get("deployments/{$deployment['id']}/statuses", ['per_page' => 1])[0] ?? null;
            if (($status['state'] ?? null) !== 'success') {
                continue;
            }
            $run = $runs->get((int) str($status['log_url'] ?? '')->after('/runs/')->before('/')->toString());
            $history[] = [
                'rollback' => ($run['workflow'] ?? null) === self::WORKFLOWS['rollback'],
                'sha'      => $deployment['sha'],
                'since'    => $status['created_at'],
                'by'       => $deployment['creator']['login'] ?? null,
                'run_url'  => $status['log_url'] ?? null,
            ];
        }

        [$live, $idle] = [null, null];
        foreach (array_reverse($history) as $event) {
            if (! $event['rollback']) {
                [$live, $idle] = [$event, $live];
            } elseif ($idle) {
                [$live, $idle] = [[...$idle, 'since' => $event['since'], 'by' => $event['by'], 'rolled_back' => true], $live];
            }
        }

        if (! $live) {
            return null;
        }

        return [
            ...$this->commit($this->get("commits/{$live['sha']}")),
            'since'       => $live['since'],
            'by'          => $live['by'],
            'run_url'     => $live['run_url'],
            'rolled_back' => $live['rolled_back'] ?? false,
        ];
    }

    /** How many commits of main a release lacks */
    private function behind(string $sha, string $main): int
    {
        return $sha === $main ? 0 : (int) ($this->get("compare/{$sha}...{$main}")['ahead_by'] ?? 0);
    }

    private function commit(array $commit): array
    {
        return [
            'sha'     => $commit['sha'],
            'short'   => substr($commit['sha'], 0, 7),
            'message' => str($commit['commit']['message'] ?? '')->before("\n")->toString(),
            'date'    => $commit['commit']['committer']['date'] ?? null,
        ];
    }

    private function get(string $path, array $query = []): array
    {
        return $this->client()->get($path, $query)->throw()->json() ?? [];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl('https://api.github.com/repos/' . config('delivery.github.repository') . '/')
            ->withToken((string) config('delivery.github.token'))
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
            ->acceptJson()
            ->timeout(10);
    }
}
