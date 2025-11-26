<?php

namespace App\Services\Form\Lib;

use Illuminate\Database\Eloquent\Model;
use JsonSerializable;

abstract class Spec implements JsonSerializable
{
    protected array $spec = [];
    protected string $fieldName;

    public function __construct(
        protected Rule $rule,
        protected ?Model $model = null
    ) {
        $this->buildSpec();

        $this->spec['label'] = $this->generateLabel();
    }

    abstract protected function buildSpec(): void;

    public function toArray(): array
    {
        return $this->spec;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Infer a human-readable, localizable label from the field name.
     */
    protected function generateLabel(): string
    {
        $field = $this->rule->getFieldName();

        $label = preg_replace('/^is_/', '', $field);    // Remove is_ prefix (bool field)
        $label = preg_replace('/_ids?$/', '', $label);  // Remove id/ids suffix
        $label = str_replace('_', ' ', $label);         // Separate words
        $label = ucwords($label);                       // Capitalize words

        // Pluralize *_ids field's label
        if (str_ends_with($field, '_ids')) {
            $label = str($label)->plural()->toString();
        }

        return $label;
    }
}
