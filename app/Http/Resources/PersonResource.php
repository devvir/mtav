<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Someone as a face beside what they wrote or where they are: their name and picture, nothing
 * else about them
 *
 * @mixin User
 */
class PersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $person */
        $person = $this->resource;

        return [
            'id'     => $person->id,
            'name'   => $person->name,
            'avatar' => $person->avatar_url,
        ];
    }
}
