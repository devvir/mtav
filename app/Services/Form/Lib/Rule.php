<?php

// Copilot - Pending review

namespace App\Services\Form\Lib;

use InvalidArgumentException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Unique;
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
            $rule instanceof Stringable     => $this->parseStringRule((string) $rule),
            $rule instanceof Exists         => $this->parseExistsRule($rule),
            $rule instanceof Unique         => $this->parseUniqueRule($rule),
            $rule instanceof In             => $this->parseInRule($rule),
            $rule instanceof Enum           => $this->parseEnumRule($rule),
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
            'in'     => $this->parsed['in'] = array_combine($parameters, $parameters),
            'exists' => $this->parsed['exists'] = $this->parseExistsParameters($parameters),
            'unique' => $this->parsed['unique'] = $this->parseUniqueParameters($parameters),
            default  => null, // Ignore other string rules
        };
    }

    protected function parseExistsRule(Exists $rule): void
    {
        $reflection = new ReflectionClass($rule);
        $property = $reflection->getProperty('table');
        $table = $property->getValue($rule);

        $this->parsed['exists'] = $this->tableToModelClass($table);
    }

    protected function parseUniqueRule(Unique $rule): void
    {
        $reflection = new ReflectionClass($rule);
        $property = $reflection->getProperty('table');
        $table = $property->getValue($rule);

        $this->parsed['unique'] = $this->tableToModelClass($table);
    }

    protected function parseInRule(In $rule): void
    {
        $reflection = new ReflectionClass($rule);
        $property = $reflection->getProperty('values');
        $values = $property->getValue($rule);

        $values = is_array($values) ? $values : [$values];
        $this->parsed['in'] = array_combine($values, $values);
    }

    protected function parseEnumRule(Enum $rule): void
    {
        $reflection = new ReflectionClass($rule);
        $property = $reflection->getProperty('type');
        $enumClass = $property->getValue($rule);

        // Extract only and except constraints
        $onlyProperty = $reflection->getProperty('only');
        $only = $onlyProperty->getValue($rule);

        $exceptProperty = $reflection->getProperty('except');
        $except = $exceptProperty->getValue($rule);

        // Determine valid cases
        $validCases = !empty($only) ? $only : $enumClass::cases();

        if (!empty($except)) {
            $exceptValues = array_map(fn ($case) => $case->value ?? $case->name, $except);
            $validCases = array_filter($validCases, fn ($case) => !in_array($case->value ?? $case->name, $exceptValues, true));
        }

        // Map to [value => label] format (same as 'in' rule)
        $options = [];
        foreach ($validCases as $case) {
            $value = $case->value ?? $case->name;
            $label = method_exists($case, 'label') ? $case->label() : $case->name;
            $options[$value] = $label;
        }

        $this->parsed['in'] = $options;
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

    protected function parseExistsParameters(array $parameters): string
    {
        $modelClass = $this->tableToModelClass($parameters[0]);

        if ($modelClass === null) {
            throw new InvalidArgumentException(
                "Cannot infer model from exists rule. Consider using Rule::exists(Model::class) instead of the literal model table."
            );
        }

        return $modelClass;
    }

    protected function parseUniqueParameters(array $parameters): string
    {
        $modelClass = $this->tableToModelClass($parameters[0]);

        if ($modelClass === null) {
            throw new InvalidArgumentException(
                "Cannot infer model from unique rule. Consider using Rule::unique(Model::class) instead of the literal model table."
            );
        }

        return $modelClass;
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
