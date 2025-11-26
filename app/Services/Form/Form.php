<?php

namespace App\Services\Form;

use Illuminate\Support\Str;
use JsonSerializable;

class Form implements JsonSerializable
{
    public function __construct(
        protected DataProvider $provider
    ) {
        // ...
    }

    public function jsonSerialize(): array
    {
        return [
            'type'   => $this->provider->type->value, // create | update
            'entity' => Str::snake($this->provider->modelName),
            'action' => $this->provider->formAction(),
            'title'  => $this->provider->formTitle(),
            'specs'  => $this->provider->specs(),
        ];
    }
}
