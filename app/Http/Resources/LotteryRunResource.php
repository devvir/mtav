<?php

namespace App\Http\Resources;

use App\Models\LotteryRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A lottery run as everyone in the project sees it, invalidation reason included.
 *
 * @mixin LotteryRun
 */
class LotteryRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'status'              => $this->status->value,
            'started_at'          => $this->started_at,
            'finished_at'         => $this->finished_at,
            'started_by'          => $this->whenLoaded('starter', fn () => $this->starter?->name),
            'failure'             => $this->failure,
            'invalidated_at'      => $this->invalidated_at,
            'invalidated_by'      => $this->whenLoaded('invalidator', fn () => $this->invalidator?->name),
            'invalidation_reason' => $this->invalidation_reason,
        ];
    }
}
