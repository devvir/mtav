<?php

use App\Services\Lottery\AuditService;
use App\Services\Lottery\LotteryOrchestrator;
use App\Services\Lottery\Solvers\GlpkSolver;

/**
 * Equals are equally likely: two families with the same ranking both win the disputed unit
 * over enough runs, and a run drawn again from its record falls the same way.
 */
uses()->group('Unit.Lottery.Glpk', 'slow');

beforeEach(function () {
    config()->set('logging.default', 'null');
    fakeAudits();
});

it('draws the ties, and the same run draws them the same way', function () {
    $group = ['participants' => [1 => [10, 20], 2 => [10, 20]], 'items' => [10, 20]];
    $lottery = new LotteryOrchestrator(app(GlpkSolver::class), app(AuditService::class));

    $winners = collect(range(1, 24))->map(function () use ($group, $lottery) {
        $manifest = mockManifest(1, [7 => $group]);
        $picks = $lottery->execute($manifest)->picks;

        expect($lottery->execute($manifest)->picks)->toBe($picks);

        return $picks[1];
    });

    // Each family got the first unit in some run: a 2^-23 chance of failing here by bad luck
    expect($winners->unique()->sort()->values()->all())->toBe([10, 20]);
});
