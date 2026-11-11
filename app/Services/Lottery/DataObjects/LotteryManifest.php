<?php

namespace App\Services\Lottery\DataObjects;

use JsonSerializable;

/**
 * Everything a lottery run decides from, as plain data (no models): for each group,
 * every participant's complete ranking and the items available.
 *
 * $data = [
 *     <group_id> => [
 *         'participants' => [<participant_id> => [<item_id>, …], …],  // most wanted first
 *         'items'        => [<item_id>, …],
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
        public readonly ?int $projectId,
        public readonly int $runId,
        public readonly array $data,
        public readonly array $options = [],
    ) {
    }

    /** A manifest as its run recorded it (its `init`), to decide from exactly what was written */
    public static function recorded(array $record, int $runId): self
    {
        return new self($record['uuid'], $record['project_id'] ?? null, $runId, $record['data'] ?? [], $record['options'] ?? []);
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
