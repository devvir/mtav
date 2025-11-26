<?php

namespace App\Services\Form;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionNamedType;

class DataProvider
{
    public readonly ?Model $model;
    public readonly string $modelName;
    public readonly string $modelClass;

    protected FormRequest $request;
    protected Specs $specs;

    /**
     * @param Model|class-string $model
     */
    public function __construct(Model|string $model, public readonly FormType $type)
    {
        $this->model = is_string($model) ? null : $model;
        $this->modelClass = is_string($model) ? $model : get_class($model);
        $this->modelName = class_basename($this->modelClass);

        $this->request = $this->formRequest();

        if ($type === FormType::UPDATE) {
            $this->injectModel();
        }

        $this->specs = Specs::make($this->request, $this->model);
    }

    /**
     * Convert FormRequest rules to frontend FormSpecs.
     */
    public function specs(): Specs
    {
        return $this->specs;
    }

    /**
     * The route for the HTML form's action (route to submit it to).
     */
    public function formAction(): array
    {
        $namespace = Str::plural(Str::snake($this->modelName));

        $route = $namespace . match ($this->type) {
            FormType::CREATE => '.store',
            FormType::UPDATE => '.update',
        };

        $params = $this->type === FormType::CREATE ? null : $this->model->getKey();

        return compact('route', 'params');
    }

    public function formTitle(): string
    {
        $entityName = Str::headline($this->modelName);

        return $this->type === FormType::CREATE
            ? __('general.create_entity', ['entity' => __($entityName)])
            : __('general.edit_entity', ['entity' => __($entityName)]);
    }

    protected function formRequest(): FormRequest
    {
        $namespace = config('forms.namespaces.formrequests');

        $requestClass = match ($this->type) {
            FormType::CREATE => "\\{$namespace}\\Create{$this->modelName}Request",
            FormType::UPDATE => "\\{$namespace}\\Update{$this->modelName}Request",
        };

        return $requestClass::createFrom(request());
    }

    protected function injectModel(): void
    {
        if (! $this->model) {
            throw new BadMethodCallException('No model provided to inject into FormRequest.');
        }

        $modelRouteName = $this->modelArgumentName();

        $this->request->route()->setParameter($modelRouteName, $this->model);
    }

    protected function modelArgumentName(): string
    {
        $namespace = config('forms.namespaces.controllers');
        $controller = "{$namespace}\\{$this->modelName}Controller";
        $reflection = new ReflectionClass($controller);

        foreach ($reflection->getMethod('edit')->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && $type->getName() === get_class($this->model)) {
                return $parameter->getName();
            }
        }

        throw new BadMethodCallException('Unable to get argument name for injecting the model into FormRequest.');
    }
}
