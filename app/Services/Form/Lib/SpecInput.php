<?php

namespace App\Services\Form\Lib;

class SpecInput extends Spec
{
    protected function buildSpec(): void
    {
        $this->spec['element'] = 'input';
        $this->spec['type'] = $this->determineInputType();
        $this->spec['required'] = (bool) $this->rule->required;

        if (isset($this->rule->min)) {
            $this->spec['min'] = $this->rule->min;
        }

        if (isset($this->rule->max)) {
            $this->spec['max'] = $this->rule->max;
        }

        if ($this->rule->between) {
            [$min, $max] = $this->rule->between;
            $this->spec['min'] = $min;
            $this->spec['max'] = $max;
        }

        $this->spec['value'] = $this->determineSelectedValue();
    }

    protected function determineInputType(): string
    {
        return match ($this->rule->type) {
            'integer',
            'numeric' => 'number',
            'email'   => 'email',
            'url'     => 'url',
            'date'    => 'datetime-local',
            'file'    => 'file',
            'image'   => 'file',
            default   => 'text',
        };
    }

    protected function determineSelectedValue(): mixed
    {
        $attr = $this->rule->getFieldName();

        return $this->model?->hasAttribute($attr)
            ? $this->model?->getAttribute($attr)
            : null;
    }
}
