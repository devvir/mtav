<?php

namespace App\Http\Resources;

use App\Models\LotteryAudit;
use Illuminate\Http\Request;

/**
 * @property-read LotteryAudit $resource
 *
 * @mixin LotteryAudit
 */
class LotteryAuditResource extends JsonResource
{
    public function toArray(Request $_): array
    {
        return [
            ...$this->commonResourceData(),

            'execution_uuid' => $this->execution_uuid,
            'type'           => $this->type->value,
            'audit'          => $this->audit,

            ...$this->relationsData(),
        ];
    }

    protected function relationsData(): array
    {
        return [
            'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),
            'lottery' => $this->whenLoaded('lottery', default: ['id' => $this->lottery_id]),
        ];
    }
}
