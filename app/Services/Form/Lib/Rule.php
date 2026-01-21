<?php

// Copilot - Pending review

namespace App\Services\Form\Lib;

use InvalidArgumentException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;
use JsonSerializable;
use ReflectionClass;
use Stringable;

class Rule implements JsonSerializable
{
    protected array $parsed = [];
    protected ?array $wildcardRules = null;

    protected function __construct(
        protected string $fieldName,
        mixed $rules,
        mixed $wildcardRules = null
    ) {
        $normalized = $this->normalizeRules($rules);

        foreach ($normalized as $rule) {
            $this->parseRule($rule);
        }

        // Store wildcard rules for array fields
        if ($wildcardRules !== null) {
            $this->wildcardRules = $this->normalizeRules($wildcardRules);
        }
    }

    public static function make(string $fieldName, mixed $rules, mixed $wildcardRules = null): self
    {
        return new self($fieldName, $rules, $wildcardRules);
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getWildcardRules(): ?array
    {
        return $this->wildcardRules;
    }

    public function toArray(): array
    {
        return array_filter($this->parsed);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __get(string $rule): mixed
    {
        return $this->parsed[$rule] ?? null;
    }

    public function __isset(string $rule): bool
    {
        return isset($this->parsed[$rule]);
    }

    protected function normalizeRules(mixed $rules): array
    {
        if (is_string($rules) && str_contains($rules, '|')) {
            return explode('|', $rules);
        } elseif (is_array($rules)) {
            return array_merge(...array_map($this->normalizeRules(...), $rules));
        }

        return [$rules];
    }

    protected function parseRule(mixed $rule): void
    {
        match (true) {
            is_string($rule)                => $this->parseStringRule($rule),
            $rule instanceof Enum           => $this->parseEnumRule($rule),
            $rule instanceof Stringable     => $this->parseStringRule((string) $rule),
            $rule instanceof ValidationRule => $this->parseCustomRule($rule),
            default                         => throw new InvalidArgumentException("Unsupported rule: " . get_debug_type($rule)),
        };
    }

    protected function parseStringRule(string $rule): void
    {
        // Split on first colon to separate rule name from parameters
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $parameters = isset($parts[1]) ? explode(',', $parts[1]) : [];

        match ($ruleName) {
            'required' => $this->parsed['required'] = true,
            'nullable' => $this->parsed['nullable'] = true,
            'string'   => $this->parsed['type'] = 'string',
            'integer', 'int' => $this->parsed['type'] = 'integer',
            'numeric' => $this->parsed['type'] = 'numeric',
            'boolean', 'bool' => $this->parsed['type'] = 'boolean',
            'array'   => $this->parsed['type'] = 'array',
            'date'    => $this->parsed['type'] = 'date',
            'email'   => $this->parsed['type'] = 'email',
            'url'     => $this->parsed['type'] = 'url',
            'file'    => $this->parsed['type'] = 'file',
            'image'   => $this->parsed['type'] = 'image',
            'json'    => $this->parsed['type'] = 'json',
            'min'     => $this->parsed['min'] = $this->parseNumeric($parameters[0]),
            'max'     => $this->parsed['max'] = $this->parseNumeric($parameters[0]),
            'between' => $this->parsed['between'] = [
                $this->parseNumeric($parameters[0]),
                $this->parseNumeric($parameters[1]),
            ],
            'size'   => $this->parsed['size'] = $this->parseNumeric($parameters[0]),
            'exists' => $this->parsed['exists'] = $this->tableToModelClass($parameters[0]),
            'unique' => $this->parsed['unique'] = $this->tableToModelClass($parameters[0]),
            'in'     => $this->parsed['in'] = $this->parseInParameters($parameters),
            default  => null, // Ignore other string rules
        };
    }

    /**
     * Treat Enum rules as 'in' rules (with custom labels, if the Enum implements @label()).
     */
    protected function parseEnumRule(Enum $rule): void
    {
        $this->parseStringRule((string) $rule);

        $reflection = new ReflectionClass($rule);
        $enumClass = $reflection->getProperty('type')->getValue($rule);

        foreach ($this->parsed['in'] as $value) {
            $case = $enumClass::tryFrom($value);

            if ($case && method_exists($case, 'label')) {
                $this->parsed['in'][$value] = $case->label();
            }
        }
    }

    /**
     * Custom/unrecognized rules are represented as [FQN => properties].
     */
    protected function parseCustomRule(ValidationRule $rule): void
    {
        $reflection = new ReflectionClass($rule);

        foreach ($reflection->getProperties() as $property) {
            $propertyName = $property->getName();
            $propertyValue = $property->getValue($rule);

            $properties[$propertyName] = $this->normalizePropertyValue($propertyValue);
        }

        $this->parsed['customRules'][get_class($rule)] = $properties ?? [];
    }

    protected function normalizePropertyValue(mixed $value): mixed
    {
        // For scalar values, return as-is
        if (is_scalar($value) || is_null($value)) {
            return $value;
        }

        // For objects, store class name (can be expanded later if needed)
        if (is_object($value)) {
            return [
                '__class__'          => get_class($value),
                '__representation__' => method_exists($value, '__toString') ? (string) $value : get_class($value),
            ];
        }

        // For arrays, recursively normalize
        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizePropertyValue($item), $value);
        }

        // Fallback: convert to string representation
        return (string) $value;
    }

    protected function parseInParameters(array $parameters): array
    {
        $values = array_map(fn ($param) => trim($param, '"'), $parameters);

        return array_combine($values, $values);
    }

    /**
     * Convert table name to Model class name, e.g. users > App\Models\User.
     * Returns null if the inferred model doesn't exist or its table doesn't match.
     */
    protected function tableToModelClass(string $table): ?string
    {
        $singular = str($table)->singular()->studly();
        $namespace = config('forms.namespaces.models');
        $fqn = "{$namespace}\\{$singular}";

        // Verify the model class exists
        if (! class_exists($fqn) || (new $fqn())->getTable() !== $table) {
            return null;
        }

        return $fqn;
    }

    protected function parseNumeric(string $value): int|float
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Invalid numeric parameter: '{$value}'");
        }

        return str_contains($value, '.') ? (float) $value : (int) $value;
    }
}
