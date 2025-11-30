<?php

namespace App\Services\Form;

use App\Services\Form\Lib\Spec;
use Illuminate\Support\Str;
use JsonSerializable;

class Form implements JsonSerializable
{
    /**
     * Local "copy" of the generated specs, allows post-generation customization.
     *
     * @param array<string, Spec|array>
     */
    protected array $specs;

    public function __construct(protected DataProvider $provider)
    {
        $this->specs = $this->provider->specs()->toArray();
    }

    public function addSpec(string $key, array $spec): static
    {
        $this->specs[$key] = $spec;

        return $this;
    }

    public function removeSpec(string $key): static
    {
        unset($this->specs[$key]);

        return $this;
    }

    public function removeSpecs(array|string $keys): static
    {
        is_array($keys) || ($keys = func_get_args());

        collect($keys)->each($this->removeSpec(...));

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'type'   => $this->provider->type->value, // create | update
            'entity' => Str::snake($this->provider->modelName),
            'action' => $this->provider->formAction(),
            'title'  => $this->provider->formTitle(),
            'specs'  => $this->specs,
        ];
    }
}
