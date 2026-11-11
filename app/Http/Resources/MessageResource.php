<?php

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A message as the chat shows it to whoever reads it: who wrote it, whether it is theirs, the
 * Assistant's buttons, and until when they may still change it. Its author comes loaded.
 *
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mine = $this->user_id === $request->user()->id;

        return [
            'id'             => $this->id,
            'body'           => $this->body,
            'author'         => $this->author ? (new PersonResource($this->author))->resolve($request) : null,
            'mine'           => $mine,
            'from_assistant' => $this->from_assistant,
            'actions'        => $this->extras['actions'] ?? [],
            'created_at'     => $this->created_at,
            // Theirs, it may still be edited or deleted until then
            'changeable_until' => $mine && $this->stillChangeable() ? $this->changeableUntil() : null,
        ];
    }
}
