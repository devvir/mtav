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
