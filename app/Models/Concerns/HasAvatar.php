<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

/**
 * A picture of its own (a person's, a family's), kept on the public disk: `avatar` is where,
 * `avatar_url` is the address browsers load it from (null with none).
 */
trait HasAvatar
{
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->avatar ? Storage::disk('public')->url($this->avatar) : null);
    }
}
