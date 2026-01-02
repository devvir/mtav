<?php

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;

/**
 * @property-read Notification $resource
 *
 * @mixin Notification
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->commonResourceData(),

            'id'        => $this->id,
            'data'      => $this->data,
            'target'    => $this->target->value,
            'target_id' => $this->target_id,
            'is_read'   => $this->whenHas('read', fn () => $this->read),
        ];
    }
}
