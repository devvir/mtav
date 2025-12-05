<?php

use App\Services\Form\FormType;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * Assert that the Inertia response from Controller actions using FormService
 * matches the expected output, with all relevant keys and their right values.
 *
 * @param  TestResponse  $response  The response to test
 * @param  FormType      $type      Type of form under test
 * @param  string        $entity    Entity under test (e.g., 'admin', 'unit_type')
 * @param  int|null      $entityId  For update forms, the ID of the entity being updated
 */
function assertFormGeneration(
    TestResponse $response,
    FormType $type,
    string $entity,
    ?int $entityId = null,
    ?array $specs = null,
): void {
    // Infer Component from entity + type
    $entityPlural = Str::plural(Str::studly($entity));
    $componentAction = $type === FormType::CREATE ? 'Create' : 'Edit';

    // Infer route from entity + type
    $routePlural = Str::plural($entity);
    $routeAction = $type === FormType::CREATE ? 'store' : 'update';
    $route = "{$routePlural}.{$routeAction}";

    $entityName = Str::headline(Str::studly($entity));
    $titleKey = $type === FormType::CREATE ? 'general.create_entity' : 'general.edit_entity';

    expect($response)->toUsePage("{$entityPlural}/{$componentAction}");
    expect($response)->toHaveProp("form.type", $type->value);
    expect($response)->toHaveProp("form.entity", $entity);
    expect($response)->toHaveProp("form.action", ['route' => $route, 'params' => $entityId]);
    expect($response)->toHaveProp("form.title", __($titleKey, ['entity' => __($entityName)]));
    expect($response)->toHaveProp('form.specs', $specs);
}

/**
 * Extract form specs from a response that contains FormService data.
 *
 * @param  TestResponse  $response  Response from a create/edit endpoint
 * @return array Form specs array
 */
function extractFormSpecs(TestResponse $response): array
{
    $form = $response->viewData('page')['props']['form'];

    // Form object implements JsonSerializable, so serialize it first
    if (is_object($form) && method_exists($form, 'jsonSerialize')) {
        $form = $form->jsonSerialize();
    }

    $specs = $form['specs'];

    // Specs may be objects that implement JsonSerializable (SpecInput/SpecSelect)
    // Convert them to arrays for easier manipulation in tests
    $serializedSpecs = [];
    foreach ($specs as $fieldName => $spec) {
        if (is_object($spec) && method_exists($spec, 'jsonSerialize')) {
            $serializedSpecs[$fieldName] = $spec->jsonSerialize();
        } else {
            $serializedSpecs[$fieldName] = $spec;
        }
    }

    return $serializedSpecs;
}

/**
 * Extract complete form data (specs, action, etc) from a response.
 *
 * @param  TestResponse  $response  Response from a create/edit endpoint
 * @return array Complete form data
 */
function extractFormData(TestResponse $response): array
{
    $form = $response->viewData('page')['props']['form'];

    // Form object implements JsonSerializable, so serialize it first
    if (is_object($form) && method_exists($form, 'jsonSerialize')) {
        $form = $form->jsonSerialize();
    }

    return $form;
}

/**
 * Generate form submission data from specs with "as-received" values.
 * For fields with value/selected, keep those values.
 * For fields without values, submit them empty (null for scalars, [] for arrays).
 *
 * @param  array  $specs  Form specs from FormService
 * @return array Form data ready for submission
 */
function generateEmptyFormData(array $specs): array
{
    $data = [];

    foreach ($specs as $fieldName => $spec) {
        // Include hidden fields - they're hidden from UI but required for validation
        if ($spec['element'] === 'select') {
            $defaultValue = $spec['selected'] ?? null;
            // If field accepts multiple values, default to empty array
            if (($spec['multiple'] ?? false) && $defaultValue === null) {
                $defaultValue = [];
            }
            $data[$fieldName] = $defaultValue;
        } else {
            $data[$fieldName] = $spec['value'] ?? null;
        }
    }

    return $data;
}

/**
 * Generate valid form submission data from specs.
 * Creates appropriate test values based on field type and constraints.
 *
 * @param  array  $specs  Form specs from FormService
 * @param  array  $overrides  Optional field overrides
 * @return array Form data ready for submission
 */
function generateValidFormData(array $specs, array $overrides = []): array
{
    $data = [];

    foreach ($specs as $fieldName => $spec) {
        // Use override if provided
        if (array_key_exists($fieldName, $overrides)) {
            $data[$fieldName] = $overrides[$fieldName];
            continue;
        }

        // Include hidden fields - they're hidden from UI but required for validation
        // For hidden fields, use their current value
        if ($spec['hidden'] ?? false) {
            $value = $spec['selected'] ?? $spec['value'] ?? null;
            // If it's a multi-select and value is null, use empty array
            if ($spec['element'] === 'select' && ($spec['multiple'] ?? false) && $value === null) {
                $value = [];
            }
            $data[$fieldName] = $value;
            continue;
        }

        // Generate appropriate value based on field type
        $data[$fieldName] = generateValidFieldValue($spec);
    }

    return $data;
}

/**
 * Generate a valid value for a single field based on its spec.
 *
 * @param  array  $spec  Field specification
 * @return mixed Valid value for the field
 */
function generateValidFieldValue(array $spec): mixed
{
    if ($spec['element'] === 'select') {
        return generateValidSelectValue($spec);
    }

    return generateValidInputValue($spec);
}

/**
 * Generate a valid value for a select field.
 *
 * @param  array  $spec  Select field specification
 * @return mixed Valid select value
 */
function generateValidSelectValue(array $spec): mixed
{
    $options = $spec['options'];

    // Handle project-scoped selects (nested arrays)
    if (is_array(reset($options)) && !empty($options)) {
        $firstProject = reset($options);
        $options = $firstProject;
    }

    if (empty($options)) {
        return $spec['multiple'] ?? false ? [] : null;
    }

    $keys = array_keys($options);

    if ($spec['multiple'] ?? false) {
        // For multiple select, return array with first option
        return [$keys[0]];
    }

    // For single select, return first option key
    return $keys[0];
}

/**
 * Generate a valid value for an input field.
 *
 * @param  array  $spec  Input field specification
 * @return mixed Valid input value
 */
function generateValidInputValue(array $spec): mixed
{
    $type = $spec['type'] ?? 'text';
    $required = $spec['required'] ?? false;

    // Non-required fields can be null
    if (!$required) {
        return null;
    }

    return match ($type) {
        'email' => 'test@example.com',
        'number' => generateValidNumber($spec),
        'date' => generateValidDate($spec),
        'datetime-local' => generateValidDateTime($spec),
        'text', 'password' => generateValidString($spec),
        'checkbox' => true,
        default => generateValidString($spec),
    };
}

/**
 * Generate a valid number within min/max constraints.
 *
 * @param  array  $spec  Field specification
 * @return int|float Valid number
 */
function generateValidNumber(array $spec): int|float
{
    $min = $spec['min'] ?? 0;
    $max = $spec['max'] ?? 100;

    // Use midpoint or min if available
    return is_float($min) || is_float($max)
        ? ($min + $max) / 2
        : (int) (($min + $max) / 2);
}

/**
 * Generate a valid string within min/max length constraints.
 *
 * @param  array  $spec  Field specification
 * @return string Valid string
 */
function generateValidString(array $spec): string
{
    $min = $spec['min'] ?? 1;
    $max = $spec['max'] ?? 50;

    // Use a length that satisfies the constraint
    $length = max($min, min(10, $max));

    return str_repeat('a', $length);
}

/**
 * Generate a valid date within min/max constraints.
 *
 * @param  array  $spec  Field specification
 * @return string Valid date string
 */
function generateValidDate(array $spec): string
{
    $min = $spec['min'] ?? null;
    $max = $spec['max'] ?? null;

    if ($min) {
        return $min;
    }

    if ($max) {
        return $max;
    }

    return now()->format('Y-m-d');
}

/**
 * Generate a valid datetime within min/max constraints.
 *
 * @param  array  $spec  Field specification
 * @return string Valid datetime string
 */
function generateValidDateTime(array $spec): string
{
    $min = $spec['min'] ?? null;
    $max = $spec['max'] ?? null;

    if ($min) {
        return $min;
    }

    if ($max) {
        return $max;
    }

    return now()->format('Y-m-d\TH:i');
}

/**
 * Generate invalid form data for testing validation errors.
 *
 * @param  array  $specs  Form specs from FormService
 * @param  string  $invalidField  Which field to make invalid
 * @param  string  $invalidationType  Type of validation error to trigger
 * @return array Form data with one invalid field
 */
function generateInvalidFormData(
    array $specs,
    string $invalidField,
    string $invalidationType = 'required'
): array {
    $data = generateValidFormData($specs);

    $spec = $specs[$invalidField] ?? null;

    if (!$spec) {
        throw new InvalidArgumentException("Field '{$invalidField}' not found in specs");
    }

    $data[$invalidField] = generateInvalidFieldValue($spec, $invalidationType);

    return $data;
}

/**
 * Generate an invalid value for a field based on the type of violation.
 *
 * @param  array  $spec  Field specification
 * @param  string  $invalidationType  Type of validation error
 * @return mixed Invalid value
 */
function generateInvalidFieldValue(array $spec, string $invalidationType): mixed
{
    return match ($invalidationType) {
        'required' => null,
        'too_short' => generateTooShortValue($spec),
        'too_long' => generateTooLongValue($spec),
        'below_min' => generateBelowMinValue($spec),
        'above_max' => generateAboveMaxValue($spec),
        'invalid_type' => generateInvalidTypeValue($spec),
        'invalid_format' => generateInvalidFormatValue($spec),
        default => null,
    };
}

/**
 * Generate a value that's too short for the field.
 */
function generateTooShortValue(array $spec): string
{
    $min = $spec['min'] ?? 1;
    return $min > 1 ? str_repeat('a', $min - 1) : '';
}

/**
 * Generate a value that's too long for the field.
 */
function generateTooLongValue(array $spec): string
{
    $max = $spec['max'] ?? 255;
    return str_repeat('a', $max + 1);
}

/**
 * Generate a number below the minimum.
 */
function generateBelowMinValue(array $spec): int|float
{
    $min = $spec['min'] ?? 0;
    return $min - 1;
}

/**
 * Generate a number above the maximum.
 */
function generateAboveMaxValue(array $spec): int|float
{
    $max = $spec['max'] ?? 100;
    return $max + 1;
}

/**
 * Generate a value of the wrong type.
 */
function generateInvalidTypeValue(array $spec): mixed
{
    $type = $spec['type'] ?? 'text';

    return match ($type) {
        'email' => 'not-an-email',
        'number' => 'not-a-number',
        'date' => 'not-a-date',
        'datetime-local' => 'not-a-datetime',
        default => 12345, // Submit number instead of string
    };
}

/**
 * Generate a value with invalid format.
 */
function generateInvalidFormatValue(array $spec): mixed
{
    $type = $spec['type'] ?? 'text';

    return match ($type) {
        'email' => 'invalid-email-format',
        'date' => '2024-13-45', // Invalid date
        'datetime-local' => '2024-13-45T25:99',
        default => 'invalid',
    };
}
