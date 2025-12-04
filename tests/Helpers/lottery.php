<?php

use App\Services\Lottery\DataObjects\LotteryManifest;
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
        array $data = []
    ) {
        $this->uuid = $uuid;
        $this->projectId = $projectId;
        $this->lotteryId = $lotteryId;
        $this->data = $data;
    }
}

/**
 * Create a mock lottery manifest for testing.
 */
function mockManifest(
    int $projectId = 1,
    array $data = [],
    int $lotteryId = 1,
    ?string $uuid = null
): MockManifest {
    $uuid ??= Str::uuid()->toString();

    return new MockManifest($uuid, $projectId, $lotteryId, $data);
}
