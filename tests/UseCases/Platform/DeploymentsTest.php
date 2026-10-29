<?php

use App\Services\Delivery\GitHub;
use Illuminate\Support\Facades\Http;

/**
 * Deploying from the console: only superadmins listed as deployers; what each environment
 * runs, read from GitHub (a rollback brings the release before back); each deploy asked
 * of GitHub, never run here.
 */

const REPO = 'https://api.github.com/repos/devvir/mtav/';

beforeEach(function () {
    config([
        'delivery.deployers'    => ['ana@example.com'],
        'delivery.github.token' => 'token',
    ]);
    $this->deployer = aUser(['email' => 'ana@example.com', 'is_superadmin' => true]);
});

/** GitHub as it answers: these runs, and staging's deployments (newest first) */
function githubWith(array $deployments = [], array $runs = []): void
{
    $commit = fn ($sha) => ['sha' => $sha, 'commit' => ['message' => "Commit {$sha}\n\nbody", 'committer' => ['date' => '2026-10-29T10:00:00Z']]];
    $fakes = [
        REPO . 'commits/main'        => Http::response($commit('c3')),
        REPO . 'actions/runs*'       => Http::response(['workflow_runs' => $runs]),
        REPO . 'branches*'           => Http::response([['name' => 'main'], ['name' => 'plan-guides']]),
        REPO . 'compare/*'           => Http::response(['ahead_by' => 2]),
        REPO . 'actions/workflows/*' => Http::response(null, 204),
    ];
    foreach (['c1', 'c2', 'c3'] as $sha) {
        $fakes[REPO . "commits/{$sha}"] = Http::response($commit($sha));
    }
    foreach ($deployments as $index => [$sha, $run]) {
        $fakes[REPO . "deployments/{$index}/statuses*"] = Http::response([[
            'state'   => 'success', 'created_at' => "2026-10-29T0{$index}:00:00Z",
            'log_url' => "https://github.com/devvir/mtav/actions/runs/{$run}/job/1",
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

it('asks GitHub to deploy dev from a branch, production from main, and to roll back', function () {
    githubWith();
    $this->as($this->deployer);

    $this->post(route('admin.deployments.dev'), ['branch' => 'plan-guides', 'fresh' => true])->assertSessionHasNoErrors();
    $this->post(route('admin.deployments.production'), ['commit' => null])->assertSessionHasNoErrors();
    $this->post(route('admin.deployments.rollback'), ['environment' => 'staging'])->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), 'workflows/dev.yml/dispatches')
        && $request['ref'] === 'plan-guides' && $request['inputs'] === ['fresh' => 'true']);
    Http::assertSent(fn ($request) => str_ends_with($request->url(), 'workflows/production.yml/dispatches') && $request['ref'] === 'main');
    Http::assertSent(fn ($request) => str_ends_with($request->url(), 'workflows/rollback.yml/dispatches')
        && $request['inputs'] === ['environment' => 'staging']);
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
