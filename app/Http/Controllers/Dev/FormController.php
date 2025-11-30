<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Form\FormService;
use App\Services\Form\FormType;
use Illuminate\Database\Eloquent\Model;
use Inertia\Response;

class FormController extends Controller
{
    /**
     * Display form component previews and testing interface.
     */
    public function __invoke(): Response
    {
        $sampleProject = Project::current() ?? Project::first();

        $formSpecs = [
            ...$this->sampleFormsFor($sampleProject),
            ...$this->sampleFormsFor($sampleProject->admins()->first()),
            ...$this->sampleFormsFor($sampleProject->members()->first()),
            ...$this->sampleFormsFor($sampleProject->families()->first()),
            ...$this->sampleFormsFor($sampleProject->units()->first()),
            ...$this->sampleFormsFor($sampleProject->unitTypes()->first()),
            ...$this->sampleFormsFor($sampleProject->events()->online()->first()),
        ];

        return inertia('Dev/Forms', [
            'formSpecs' => $formSpecs,
        ]);
    }

    /**
     * Generate form specifications for a given model class and sample instance.
     *
     * @param class-string<Model> $modelClass
     */
    protected function sampleFormsFor(Model $sampleModel): array
    {
        return [
            class_basename($sampleModel) => [
                'create' => FormService::make(get_class($sampleModel), FormType::CREATE),
                'update' => FormService::make($sampleModel, FormType::UPDATE),
            ],
        ];
    }
}
