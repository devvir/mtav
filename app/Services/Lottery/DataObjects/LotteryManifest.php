<?php

namespace App\Services\Lottery\DataObjects;

use JsonSerializable;

/**
 * Everything a lottery run decides from, as plain data (no models): for each unit
 * type, every family's complete ranking and the units available.
 *
 * $data = [
 *     <unit_type_id> => [
 *         'families' => [<family_id> => [<unit_id>, …], …],  // most wanted first
 *         'units'    => [<unit_id>, …],
 *     ],
 *     …
 * ]
 */
class LotteryManifest implements JsonSerializable
{
    /**
     * @param array<string> $options  Confirmations given by the admin who started the run
     */
    public function __construct(
        public readonly string $uuid,
        public readonly int $projectId,
        public readonly int $runId,
        public readonly array $data,
        public readonly array $options = [],
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid'       => $this->uuid,
            'project_id' => $this->projectId,
            'data'       => $this->data,
            'options'    => $this->options,
        ];
    }
}
