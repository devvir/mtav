<?php

namespace App\Services\Form;

use App\Services\Form\Lib\Rule;
use App\Services\Form\Lib\Spec;
use App\Services\Form\Lib\SpecFactory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;

class Specs implements JsonSerializable
{
    /**
     * @var array<string, Spec>
     */
    protected array $specs = [];

    protected function __construct(
        protected FormRequest $request,
        protected ?Model $model = null
    ) {
        // ...
    }

    public static function make(FormRequest $request, ?Model $model = null): self
    {
        $instance = new self($request, $model);

        foreach ($instance->parseRules() as $fieldName => $rule) {
            $instance->specs[$fieldName] = SpecFactory::make($rule, $instance->model);
        }

        return $instance;
    }

    public function toArray(): array
    {
        return $this->specs;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Parse validation rules into Rule objects for form generation.
     *
     * @return array<string, Rule>
     */
    protected function parseRules(): array
    {
        $validationRules = $this->request->rules() ?? [];
        $wildcardRules = $this->extractWildcardRules($validationRules);

        return $this->buildRuleObjects($validationRules, $wildcardRules);
    }

    /**
     * Extract wildcard rules (e.g., "items.*") from validation rules.
     *
     * @return array<string, mixed>
     */
    protected function extractWildcardRules(array $validationRules): array
    {
        $wildcardRules = [];

        foreach ($validationRules as $field => $rules) {
            if ($this->isWildcardField($field)) {
                $baseField = str_replace('.*', '', $field);
                $wildcardRules[$baseField] = $rules;
            }
        }

        return $wildcardRules;
    }

    /**
     * Build Rule objects from validation rules, attaching wildcard rules where applicable.
     *
     * @return array<string, Rule>
     */
    protected function buildRuleObjects(array $validationRules, array $wildcardRules): array
    {
        $parsed = [];

        foreach ($validationRules as $field => $rules) {
            if ($this->isWildcardField($field)) {
                continue;
            }

            $parsed[$field] = Rule::make($field, $rules, $wildcardRules[$field] ?? null);
        }

        return $parsed;
    }

    protected function isWildcardField(string $field): bool
    {
        return str_contains($field, '.*');
    }
}
