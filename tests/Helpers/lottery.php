<?php

use App\Models\LotteryAudit;
use App\Services\Lottery\AuditService;
use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\Enums\LotteryAuditType;
use App\Services\Lottery\LotteryOrchestrator;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\Solvers\Glpk\DataObjects\TaskResult;
use App\Services\Lottery\Solvers\Glpk\Glpk;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\TaskRunnerFactory;
use Illuminate\Support\Str;

/**
 * A lottery manifest for tests: plain data, no database.
 */
function mockManifest(
    int $projectId = 1,
    array $data = [],
    int $runId = 1,
    ?string $uuid = null,
    array $options = []
): LotteryManifest {
    return new LotteryManifest($uuid ?? Str::uuid()->toString(), $projectId, $runId, $data, $options);
}

/**
 * Create a mocked Glpk instance with audit capture for testing.
 *
 * @param  array  $config  Optional config overrides (e.g., ['glpk_phase1_max_size' => 0])
 * @return Glpk Mock with public $auditCalls property
 */
function mockGlpkWithAuditCapture(array $config = []): Glpk
{
    // Apply config overrides
    foreach ($config as $key => $value) {
        config()->set("lottery.solvers.glpk.config.{$key}", $value);
    }

    // Create mock with auditTask method mocked
    $glpkMock = Mockery::mock(Glpk::class . '[auditTask]', [
        app(TaskRunnerFactory::class),
        app(AuditService::class)
    ]);

    $glpkMock->auditCalls = [];
    $glpkMock->shouldAllowMockingProtectedMethods()->shouldReceive('auditTask')->andReturnUsing(
        function ($_, TaskResult $result) use ($glpkMock) {
            $glpkMock->auditCalls[] = $result;
        }
    );

    return $glpkMock;
}

/**
 * Keep audit records in memory (unit tests have no lottery runs to attach them to).
 *
 * @return object{records: array<array{type: string, audit: array}>}
 */
function fakeAudits(): object
{
    $fake = new class () extends AuditService {
        public array $records = [];

        public function record(LotteryManifest|int $run, LotteryAuditType $type, array $audit): LotteryAudit
        {
            $this->records[] = ['type' => $type->value, 'audit' => $audit];

            return new LotteryAudit();
        }
    };

    app()->instance(AuditService::class, $fake);

    return $fake;
}

/**
 * An orchestrator over the given solver, auditing in memory.
 */
function orchestrator(SolverInterface $solver): LotteryOrchestrator
{
    return new LotteryOrchestrator($solver, app(AuditService::class));
}
