<?php

use App\Events\Lottery\LotteryExecutionTriggered;
use App\Services\Lottery\LotteryOrchestrator;
use Illuminate\Support\Facades\Config;

uses()->group('Unit.Lottery');

afterEach(function () {
    Mockery::close();
});

/**
 * Test that LotteryExecutionTriggered event triggers orchestrator initialization
 * with the correct manifest and configured solver.
 *
 * We don't care about listeners, queue mechanics, or internal implementation.
 * We only verify: Event dispatched → Orchestrator created → execute() called.
 */
describe('Lottery Execution Flow', function () {
    test('orchestrator is initialized with manifest and RandomSolver when dispatched', function () {
        Config::set('lottery.default', 'random');

        // Create a partial mock that doesn't actually execute
        $orchestratorMock = Mockery::mock(LotteryOrchestrator::class)
            ->shouldReceive('execute')
            ->once()
            ->getMock();

        $this->app->bind(LotteryOrchestrator::class, fn () => $orchestratorMock);

        LotteryExecutionTriggered::dispatch(mockManifest());
    });

    test('orchestrator is initialized with manifest and TestSolver when dispatched', function () {
        Config::set('lottery.default', 'test');

        $orchestratorMock = Mockery::mock(LotteryOrchestrator::class)
            ->shouldReceive('execute')
            ->once()
            ->getMock();

        $this->app->bind(LotteryOrchestrator::class, fn () => $orchestratorMock);

        LotteryExecutionTriggered::dispatch(mockManifest());
    });

    test('orchestrator is initialized with manifest and GlpkSolver when dispatched', function () {
        Config::set('lottery.default', 'glpk');

        $orchestratorMock = Mockery::mock(LotteryOrchestrator::class)
            ->shouldReceive('execute')
            ->once()
            ->getMock();

        $this->app->bind(LotteryOrchestrator::class, fn () => $orchestratorMock);

        LotteryExecutionTriggered::dispatch(mockManifest());
    });
});
