<?php

// Copilot - Pending review

namespace App\Services\Form\Lib;

use App\Models\Project;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Exists;
use InvalidArgumentException;
use ReflectionClass;
use Throwable;

class SpecSelect extends Spec
{
    protected string $fieldName;

    public function __construct(Rule $rule, ?Model $model = null)
    {
        $this->fieldName = $rule->getFieldName();

        parent::__construct($rule, $model);
    }

    protected function buildSpec(): void
    {
        $this->spec['element'] = 'select';
        $this->spec['options'] = $this->determineOptions();

        // Check if options are grouped (nested array structure)
        if ($this->isGroupedOptions($this->spec['options'])) {
            $this->spec['filteredBy'] = 'project_id';
        }

        // Set selected value
        $this->spec['selected'] = $this->determineSelectedValue();

        // Hide project_id if user has only one project
        if (in_array($this->fieldName, ['project_id', 'project_ids']) && $this->userHasOnlyOneProject()) {
            $this->spec['hidden'] = true;
        }

        // Determine if multiple select based on array type or relation type
        $this->spec['multiple'] = $this->isMultipleSelect();

        $this->spec['label'] = $this->generateLabel();

        // Add required flag
        if ($this->rule->required) {
            $this->spec['required'] = true;
        }
    }

    protected function determineOptions(): array
    {
        // Check for boolean type
        if ($this->rule->type === 'boolean') {
            return $this->getBooleanOptions();
        }

        // Check for in constraint (includes enum rules normalized by Rule class)
        if ($this->rule->in) {
            return $this->rule->in;
        }

        // If there's an exists constraint, fetch from database (except project_id which has special handling)
        if ($this->rule->exists && $this->fieldName !== 'project_id') {
            return $this->getModelOptions($this->rule->exists);
        }

        // Check wildcard rules for array fields
        if ($this->rule->type === 'array' && $this->rule->getWildcardRules()) {
            return $this->getOptionsFromWildcardRules();
        }

        // If field ends with _id, infer the model class from the field name
        if (str_ends_with($this->fieldName, '_id')) {
            $modelClass = $this->inferModelFromFieldName();
            if ($modelClass) {
                // Special handling for project_id field
                if ($this->fieldName === 'project_id') {
                    return $this->getProjectIdOptions($modelClass);
                }

                // Check for BelongsToProject rule for scoping options
                if ($this->hasBelongsToProjectRule()) {
                    return $this->getProjectScopedOptions();
                }

                return $this->getModelOptions($modelClass);
            }
        }

        return [];
    }

    protected function getProjectIdOptions(string $modelClass): array
    {
        $currentProject = Project::current();

        // If there's a current project selected, only show that project
        if ($currentProject) {
            return $this->getOptionsForModels(collect([$currentProject]), $modelClass);
        }

        // Otherwise, show all available projects
        return $this->getModelOptions($modelClass);
    }

    protected function hasBelongsToProjectRule(): bool
    {
        return isset($this->rule->customRules['App\\Rules\\BelongsToProject']);
    }

    protected function getBelongsToProjectModelClass(): ?string
    {
        return $this->rule->customRules['App\\Rules\\BelongsToProject']['modelClass'] ?? null;
    }

    protected function getProjectScopedOptions(): array
    {
        $modelClass = $this->getBelongsToProjectModelClass();

        if (!$modelClass) {
            return [];
        }

        // Convert field name to relation name
        // unit_type_id -> unitType -> unitTypes
        $singularRelation = str_replace('_id', '', $this->fieldName);
        $singularCamel = str($singularRelation)->camel()->toString();
        $pluralRelation = str($singularCamel)->plural()->toString();

        // Get all projects that will be available in the parent project_id select
        // This is determined by what projects are shown in the project_id field options
        $availableProjects = Project::alphabetically()->get();

        // Return options grouped by project_id for all available projects
        $optionsByProject = [];
        foreach ($availableProjects as $project) {
            if (!method_exists($project, $pluralRelation)) {
                continue;
            }

            $optionsByProject[$project->id] = $this->getOptionsForModels(
                $project->$pluralRelation,
                $modelClass
            );
        }

        return $optionsByProject;
    }

    protected function getOptionsFromWildcardRules(): array
    {
        $wildcardRules = $this->rule->getWildcardRules();

        if (!$wildcardRules) {
            return [];
        }

        // Parse the wildcard rules to find exists constraint
        foreach ($wildcardRules as $rule) {
            // Handle Exists rule objects
            if ($rule instanceof Exists) {
                $reflection = new ReflectionClass($rule);

                // Get the table property
                $tableProperty = $reflection->getProperty('table');
                $table = $tableProperty->getValue($rule);

                // Infer model from table name (e.g., 'users' -> 'User')
                $modelName = str($table)->singular()->studly()->toString();
                $namespace = config('forms.namespaces.models');
                $modelClass = "{$namespace}\\{$modelName}";

                if (class_exists($modelClass)) {
                    // Get where constraints if any
                    $whereProperty = $reflection->getProperty('wheres');
                    $wheres = $whereProperty->getValue($rule);

                    return $this->getModelOptions($modelClass, $wheres);
                }
            }

            // Handle string rules like 'exists:projects,id'
            if (is_string($rule) && str_starts_with($rule, 'exists:')) {
                $parts = explode(':', $rule, 2);
                if (isset($parts[1])) {
                    $params = explode(',', $parts[1]);
                    $table = $params[0];

                    // Infer model from table name
                    $modelName = str($table)->singular()->studly()->toString();
                    $namespace = config('forms.namespaces.models');
                    $modelClass = "{$namespace}\\{$modelName}";

                    if (class_exists($modelClass)) {
                        return $this->getModelOptions($modelClass);
                    }
                }
            }
        }

        return [];
    }

    protected function inferModelFromFieldName(): ?string
    {
        // Remove _id suffix and convert to StudlyCase
        // e.g., "project_id" -> "Project", "unit_type_id" -> "UnitType"
        $modelName = str($this->fieldName)
            ->replaceLast('_id', '')
            ->studly()
            ->toString();

        $namespace = config('forms.namespaces.models');
        $modelClass = "{$namespace}\\{$modelName}";

        return class_exists($modelClass) ? $modelClass : null;
    }

    protected function getBooleanOptions(): array
    {
        // Remove 'is_' prefix if present for cleaner label
        $labelBase = str_starts_with($this->fieldName, 'is_')
            ? substr($this->fieldName, 3)
            : $this->fieldName;

        // Convert to human-readable (e.g. 'published' -> 'Published')
        $label = str($labelBase)->replace('_', ' ')->title()->toString();

        // Use string keys to maintain order in JSON (true first, false second)
        return [
            'true'  => __($label),
            'false' => __('general.not_status', ['status' => __($label)]),
        ];
    }

    protected function getModelOptions(string $modelClass, array $wheres = []): array
    {
        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException("Model class does not exist: {$modelClass}");
        }

        $query = $modelClass::query();

        foreach ($wheres as $where) {
            $query->where($where['column'], $where['value']);
        }

        return $this->convertModelsToOptions($modelClass, $query->get());
    }

    protected function convertModelsToOptions(string $modelClass, Collection $models): array
    {
        $optionLabel = config('forms.optionLabel')[$modelClass] ?? 'name';

        // If optionLabel is a Closure, we need to map manually
        if ($optionLabel instanceof Closure) {
            return $models->mapWithKeys(fn ($model) => [
                $model->getKey() => $this->getModelLabel($model, $optionLabel),
            ])->toArray();
        }

        // Otherwise, use pluck for efficiency
        return $models->pluck($optionLabel, $models->first()?->getKeyName() ?? 'id')->toArray();
    }

    protected function getModelLabel(Model $model, string|Closure $optionLabel): string
    {
        // If it's a Closure, execute it with the model
        if ($optionLabel instanceof Closure) {
            return (string) $optionLabel($model);
        }

        // Check if the model has the label field
        if ($model->hasAttribute($optionLabel)) {
            return (string) $model->getAttribute($optionLabel);
        }

        // Fall back to the primary key
        return (string) class_basename($model) . ' #' . $model->getKey();
    }

    protected function determineSelectedValue(): mixed
    {
        $attr = $this->fieldName;

        return match (true) {
            $this->model?->hasAttribute($attr) => $this->model?->getAttribute($attr),
            $attr === 'project_id'             => currentProjectId(),
            default                            => null,
        };
    }

    protected function userHasOnlyOneProject(): bool
    {
        return Auth::user()?->projects->count() === 1;
    }

    protected function getOptionsForModels($models, string $modelClass): array
    {
        $optionLabel = config('forms.optionLabel')[$modelClass] ?? 'name';

        // If optionLabel is a Closure, map manually
        if ($optionLabel instanceof Closure) {
            return $models->mapWithKeys(fn ($model) => [
                $model->getKey() => $this->getModelLabel($model, $optionLabel),
            ])->toArray();
        }

        // Otherwise, use pluck
        return $models->pluck($optionLabel, $models->first()?->getKeyName() ?? 'id')->toArray();
    }

    protected function isMultipleSelect(): bool
    {
        // Check if array type (explicit multi-select)
        if ($this->rule->type === 'array') {
            return true;
        }

        // Check if field points to a to-many relation
        if (str_ends_with($this->fieldName, '_id') || str_ends_with($this->fieldName, '_ids')) {
            $modelClass = $this->inferModelFromFieldName();
            if ($modelClass && $this->isToManyRelation($modelClass)) {
                return true;
            }
        }

        return false;
    }

    protected function isToManyRelation(string $modelClass): bool
    {
        try {
            $dummyModel = new $modelClass();
            $relationName = str($this->fieldName)
                ->beforeLast('_id')
                ->beforeLast('_ids')
                ->camel()
                ->toString();

            if (!method_exists($dummyModel, $relationName)) {
                return false;
            }

            $relation = $dummyModel->$relationName();

            return $relation instanceof HasMany
                || $relation instanceof HasManyThrough
                || $relation instanceof BelongsToMany
                || $relation instanceof MorphMany
                || $relation instanceof MorphToMany;
        } catch (Throwable) {
            return false;
        }
    }

    protected function isGroupedOptions(array $options): bool
    {
        if (empty($options)) {
            return false;
        }

        // Check if first value is an array (indicating grouped structure)
        $firstValue = reset($options);
        return is_array($firstValue);
    }
}
