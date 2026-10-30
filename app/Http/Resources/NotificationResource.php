<?php

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Notification
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'type' => $this->type->value,
            ...$this->text(),
            'url'        => $this->data['url'] ?? null,
            'read'       => (bool) $this->read,
            'created_at' => $this->created_at,
        ];
    }
}
