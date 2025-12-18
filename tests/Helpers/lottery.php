<?php

use App\Services\Lottery\AuditService;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\Solvers\Glpk\DataObjects\TaskResult;
use App\Services\Lottery\Solvers\Glpk\Glpk;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\TaskRunnerFactory;
use Illuminate\Support\Str;

/**
 * Mock manifest class for testing.
 *
 * Use this in tests that need to dispatch events with manifests without
 * unnecessary database access.
 */
class MockManifest extends LotteryManifest
{
    public function __construct(
        string $uuid,
        int $projectId,
        int $lotteryId,
        array $data = [],
        array $options = []
    ) {
        $this->uuid = $uuid;
        $this->projectId = $projectId;
        $this->lotteryId = $lotteryId;
        $this->data = $data;
        $this->options = $options;
    }
}

/**
 * Create a mock lottery manifest for testing.
 */
function mockManifest(
    int $projectId = 1,
    array $data = [],
    int $lotteryId = 1,
    ?string $uuid = null,
    array $options = []
): MockManifest {
    $uuid ??= Str::uuid()->toString();

    return new MockManifest($uuid, $projectId, $lotteryId, $data, $options);
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
