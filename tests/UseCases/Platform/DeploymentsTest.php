<?php

use App\Services\Delivery\GitHub;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Deploying from the console: only superadmins listed as deployers; what each environment
 * runs, read from GitHub (a rollback brings the release before back); each deploy asked
 * of GitHub, never run here.
 */

const REPO = 'https://api.github.com/repos/devvir/mtav/';

beforeEach(function () {
    $this->withPasswordConfirmed();
    config([
        'delivery.deployers'    => ['ana@example.com'],
        'delivery.github.token' => 'token',
    ]);
    $this->deployer = aUser(['email' => 'ana@example.com', 'is_superadmin' => true]);
});

/** GitHub as it answers: these runs, and staging's deployments (newest first) */
function githubWith(array $deployments = [], array $runs = [], array $aged = []): void
{
    $commit = fn ($sha) => ['sha' => $sha, 'commit' => ['message' => "Commit {$sha}\n\nbody", 'committer' => ['date' => '2026-10-29T10:00:00Z']]];
    $fakes = [
        REPO . 'commits/main' => Http::response($commit('c3')),
        ...$aged,
        REPO . 'actions/runs*'       => Http::response(['workflow_runs' => $runs]),
        REPO . 'branches*'           => Http::response([['name' => 'main'], ['name' => 'plan-guides']]),
        REPO . 'compare/*'           => Http::response(['ahead_by' => 2]),
        REPO . 'actions/workflows/*' => Http::response(null, 204),
    ];
    foreach (['c1', 'c2', 'c3', str_repeat('a', 40)] as $sha) {
        $fakes[REPO . "commits/{$sha}"] = Http::response($commit($sha));
    }
    foreach ($deployments as $index => [$sha, $run]) {
        $fakes[REPO . "deployments/{$index}/statuses*"] = Http::response([[
            'state'           => 'success', 'created_at' => "2026-10-29T0{$index}:00:00Z",
            'log_url'         => "https://github.com/devvir/mtav/actions/runs/{$run}/job/1",
            'environment_url' => $deployments[$index][2] ?? null,
        ]]);
    }
    $fakes[REPO . 'deployments?environment=staging*'] = Http::response(array_map(
        fn ($index) => ['id' => $index, 'sha' => $deployments[$index][0], 'creator' => ['login' => 'devvir']],
        array_keys($deployments),
    ));
    $fakes[REPO . 'deployments*'] = Http::response([]);

    Http::fake($fakes);
}

function aRun(int $id, string $workflow): array
{
    return [
        'id'               => $id, 'path' => ".github/workflows/{$workflow}", 'name' => $workflow, 'display_title' => $workflow,
        'head_branch'      => 'main', 'head_sha' => 'c3c3c3c3', 'status' => 'completed', 'conclusion' => 'success',
        'html_url'         => "https://github.com/devvir/mtav/actions/runs/{$id}", 'run_started_at' => '2026-10-29T10:00:00Z',
        'triggering_actor' => ['login' => 'devvir'],
    ];
}

it('shows deployers what each environment runs, and how far behind main', function () {
    githubWith([['c2', 11], ['c1', 10]], [aRun(11, 'pipeline.yml'), aRun(10, 'pipeline.yml')]);

    $staging = collect(app(GitHub::class)->environments())->firstWhere('name', 'staging');

    expect($staging['live'])->sha->toBe('c2')->message->toBe('Commit c2')->rolled_back->toBeFalse()
        ->and($staging['behind'])->toBe(2);
});

it('knows a rollback brought the release before back', function () {
    githubWith([['c3', 12], ['c2', 11], ['c1', 10]], [aRun(12, 'rollback.yml'), aRun(11, 'pipeline.yml'), aRun(10, 'pipeline.yml')]);

    $staging = collect(app(GitHub::class)->environments())->firstWhere('name', 'staging');

    expect($staging['live'])->sha->toBe('c1')->rolled_back->toBeTrue();
});

it('knows a second rollback brought the newer release back again', function () {
    githubWith([['c3', 13], ['c3', 12], ['c2', 11], ['c1', 10]], [aRun(13, 'rollback.yml'), aRun(12, 'rollback.yml'), aRun(11, 'pipeline.yml'), aRun(10, 'pipeline.yml')]);

    $staging = collect(app(GitHub::class)->environments())->firstWhere('name', 'staging');

    expect($staging['live']['sha'])->toBe('c2');
});

it('knows a rollback whose run is older than the latest runs', function () {
    githubWith(
        [['c3', 12], ['c2', 11], ['c1', 10]],
        [aRun(11, 'pipeline.yml'), aRun(10, 'pipeline.yml')],
        [REPO . 'actions/runs/12' => Http::response(aRun(12, 'rollback.yml'))],
    );

    $staging = collect(app(GitHub::class)->environments())->firstWhere('name', 'staging');

    expect($staging['live'])->sha->toBe('c1')->rolled_back->toBeTrue();
});

it('takes a run it cannot find any more for a deploy', function () {
    githubWith([['c2', 11], ['c1', 10]], [aRun(10, 'pipeline.yml')], [REPO . 'actions/runs/11' => Http::response(null, 404)]);

    $staging = collect(app(GitHub::class)->environments())->firstWhere('name', 'staging');

    expect($staging['live']['sha'])->toBe('c2');
});

it('shows the release a run names, not the commit the run was on', function () {
    $named = str_repeat('a', 40);
    githubWith([['c3', 11, "https://www.mtav.uy/#{$named}"]], [aRun(11, 'production.yml')]);

    $staging = collect(app(GitHub::class)->environments())->firstWhere('name', 'staging');

    expect($staging['live']['sha'])->toBe($named);
});

it('reads an address with nothing named after it as the commit the run was on', function (string $url) {
    githubWith([['c2', 11, $url]], [aRun(11, 'pipeline.yml')]);

    $staging = collect(app(GitHub::class)->environments())->firstWhere('name', 'staging');

    expect($staging['live']['sha'])->toBe('c2');
})->with(['https://stg.mtav.uy', 'https://stg.mtav.uy/#', 'https://stg.mtav.uy/#not-a-commit']);

it('knows starting over keeps the release, and when it happened', function () {
    githubWith([['c3', 12], ['c2', 11], ['c1', 10]], [aRun(12, 'fresh.yml'), aRun(11, 'pipeline.yml'), aRun(10, 'pipeline.yml')]);

    $staging = collect(app(GitHub::class)->environments())->firstWhere('name', 'staging');

    expect($staging['live'])->sha->toBe('c2')->since->toBe('2026-10-29T00:00:00Z')->rolled_back->toBeFalse();
});

it('rolls back past a start over to the release before', function () {
    githubWith(
        [['c3', 13], ['c3', 12], ['c2', 11], ['c1', 10]],
        [aRun(13, 'rollback.yml'), aRun(12, 'fresh.yml'), aRun(11, 'pipeline.yml'), aRun(10, 'pipeline.yml')],
    );

    $staging = collect(app(GitHub::class)->environments())->firstWhere('name', 'staging');

    expect($staging['live']['sha'])->toBe('c1');
});

it('says so on the page when GitHub does not answer a deploy', function () {
    Http::fake([REPO . 'actions/workflows/*' => fn () => throw new ConnectionException('down')]);

    $this->as($this->deployer)->post(route('admin.deployments.rollback'), ['environment' => 'staging'])
        ->assertSessionHasErrors(['deploy' => __('flash.deploy_unreachable')]);
});

it('says so on the page when GitHub refuses a deploy', function () {
    Http::fake([REPO . 'actions/workflows/*' => Http::response(null, 403)]);

    $this->as($this->deployer)->post(route('admin.deployments.rollback'), ['environment' => 'staging'])
        ->assertSessionHasErrors(['deploy' => __('flash.deploy_refused', ['status' => 403])]);
});

it('says so on the page when GitHub does not answer which branches there are', function () {
    Http::fake([REPO . 'branches*' => fn () => throw new ConnectionException('down')]);

    $this->as($this->deployer)->post(route('admin.deployments.dev'), ['branch' => 'plan-guides'])
        ->assertSessionHasErrors(['deploy' => __('flash.deploy_unreachable')]);
});

it('asks GitHub to deploy dev from a branch, production from main, and to roll back', function () {
    githubWith();
    $this->as($this->deployer);

    $this->post(route('admin.deployments.dev'), ['branch' => 'plan-guides'])->assertSessionHasNoErrors();
    $this->post(route('admin.deployments.production'), ['commit' => null])->assertSessionHasNoErrors();
    $this->post(route('admin.deployments.rollback'), ['environment' => 'staging'])->assertSessionHasNoErrors();

    // Dev starts over on every deploy: nothing to ask, and no inputs at all (GitHub
    // refuses an empty list where it takes an object)
    Http::assertSent(fn ($request) => str_ends_with($request->url(), 'workflows/dev.yml/dispatches')
        && $request['ref'] === 'plan-guides' && ! str_contains($request->body(), 'inputs'));
    Http::assertSent(fn ($request) => str_ends_with($request->url(), 'workflows/production.yml/dispatches') && $request['ref'] === 'main');
    Http::assertSent(fn ($request) => str_ends_with($request->url(), 'workflows/rollback.yml/dispatches')
        && $request['inputs'] === ['environment' => 'staging']);
});

it('asks GitHub to start dev or staging over with the demo data, never production', function () {
    githubWith();
    $this->as($this->deployer);

    $this->post(route('admin.deployments.fresh'), ['environment' => 'staging'])
        ->assertSessionHasNoErrors()->assertSessionHas('success', __('flash.deploy_fresh', ['environment' => 'staging']));
    $this->post(route('admin.deployments.fresh'), ['environment' => 'production'])->assertSessionHasErrors('environment');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), 'workflows/fresh.yml/dispatches')
        && $request['ref'] === 'main' && $request['inputs'] === ['environment' => 'staging']);
    Http::assertSentCount(1);
});

it('deploys dev only from a branch that exists', function () {
    githubWith();

    $this->as($this->deployer)->post(route('admin.deployments.dev'), ['branch' => 'nope'])->assertSessionHasErrors('branch');

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'dispatches'));
});

it('never lets superadmins who are not deployers deploy, nor see the page', function () {
    githubWith();
    $this->as(aUser(['is_superadmin' => true]));

    $this->get(route('admin.deployments.index'))->assertForbidden();
    // A refused form goes back, saying so
    $this->post(route('admin.deployments.rollback'), ['environment' => 'staging'])->assertSessionHas('error', __('flash.not_allowed'));

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'dispatches'));
});

it('hides deploying everywhere while no GitHub token is set', function () {
    config(['delivery.github.token' => null]);

    $this->as($this->deployer)->get(route('admin.deployments.index'))->assertForbidden();
});

describe('Production', function () {
    it('is never started over by the host, whatever asked for it', function () {
        $deploy = Illuminate\Support\Facades\Process::run(['bash', base_path('deploy/host/deploy'), 'production', 'image', 'bundle', 'fresh']);

        expect($deploy->failed())->toBeTrue()
            ->and($deploy->errorOutput())->toContain('production keeps its data');
    });

    it('refuses the app\'s own wiping commands', function () {
        // --pretend: were it not refused, it would still change nothing
        $wipe = Illuminate\Support\Facades\Process::env(['MTAV_ENV' => 'production'])
            ->run(['php', base_path('artisan'), 'migrate:rollback', '--pretend', '--force']);

        expect($wipe->failed())->toBeTrue()
            ->and($wipe->output() . $wipe->errorOutput())->toContain('prohibited');
    });

    it('never holds demo data', function () {
        config(['app.deployment' => 'production']);

        expect(fn () => $this->seed(Database\Seeders\DatabaseSeeder::class))->toThrow(RuntimeException::class, 'Production never holds demo data');
    });
});

describe('What a deploy leaves', function () {
    it('gives a stopped worker longer than the longest job and the time the queue holds it', function () {
        preg_match('/stop_grace_period: (\d+)m/', file_get_contents(base_path('deploy/slot.yml')), $grace);

        expect($grace[1] * 60)->toBeGreaterThanOrEqual((new App\Jobs\RunLottery(new App\Models\LotteryRun()))->timeout)
            ->and($grace[1] * 60)->toBeGreaterThanOrEqual(config('queue.connections.database.retry_after'));
    });

    it('keeps its dumps from anyone but root', function () {
        expect(file_get_contents(base_path('deploy/host/deploy')))
            ->toContain('install -d -m 700 "$dir/backups"')
            ->toMatch('/umask 077\s+env_compose exec -T db sh -c/');
    });

    it('says what runs before waiting on the old worker', function () {
        $script = file_get_contents(base_path('deploy/host/deploy'));
        $old = 'slot_compose "$live_slot" "$live_image" down';

        expect(strpos($script, '> "$dir/live"'))->toBeLessThan(strpos($script, $old))
            ->and(strrpos($script, '> "$dir/live"'))->toBeLessThan(strrpos($script, $old));
    });

    it('leaves nothing of the release before running, on a deploy or a rollback', function () {
        $script = file_get_contents(base_path('deploy/host/deploy'));

        expect(substr_count($script, 'slot_compose "$live_slot" "$live_image" down'))->toBe(2)
            ->and($script)->not->toContain('stop worker');
    });
});
