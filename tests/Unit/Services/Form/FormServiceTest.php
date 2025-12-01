<?php

// Copilot - Pending review

uses()->group('Unit.Services.FormService');

use App\Services\Form\Form;
use App\Services\Form\FormService;
use App\Services\Form\FormType;
use Tests\Unit\Services\Form\FormRequests\TestModel;

beforeEach(function () {
    // Mock the config for namespaces
    config([
        'forms.namespaces.models'       => 'Tests\\Unit\\Services\\Form\\FormRequests',
        'forms.namespaces.controllers'  => 'Tests\\Unit\\Services\\Form\\FormRequests',
        'forms.namespaces.formrequests' => 'Tests\\Unit\\Services\\Form\\FormRequests',
    ]);

    // Mock route for UPDATE forms - only needs setParameter method
    $mockRoute = new class () {
        public function setParameter($key, $value)
        {
            // Mock implementation - does nothing, just prevents null error
        }
    };

    // Set up request mock
    request()->setRouteResolver(fn () => $mockRoute);
});

describe('FormService Factory', function () {
    it('creates a Form instance from model class string', function () {
        $form = FormService::make(TestModel::class, FormType::CREATE);

        expect($form)->toBeInstanceOf(Form::class);
    });

    it('creates a Form instance from model instance', function () {
        $model = new TestModel();
        $model->id = 1;
        $model->exists = true;
        $form = FormService::make($model, FormType::UPDATE);

        expect($form)->toBeInstanceOf(Form::class);
    });
});

describe('Form Output Structure', function () {
    it('generates correct CREATE form structure', function () {
        $form = FormService::make(TestModel::class, FormType::CREATE);
        $data = $form->jsonSerialize();
        $specs = array_map(fn ($spec) => $spec->toArray(), $data['specs']);

        expect($data)->toHaveKeys(['type', 'entity', 'action', 'title', 'specs'])
            ->and($data['type'])->toBe('create')
            ->and($data['entity'])->toBe('test_model')
            ->and($data['action'])->toBeArray()->toHaveKeys(['route', 'params'])
            ->and($data['action']['route'])->toBe('test_models.store')
            ->and($data['action']['params'])->toBeNull()
            ->and($specs)->toBeArray();
    });

    it('generates correct UPDATE form structure', function () {
        $model = new TestModel();
        $model->id = 1;
        $model->exists = true;
        $form = FormService::make($model, FormType::UPDATE);
        $data = $form->jsonSerialize();
        $specs = array_map(fn ($spec) => $spec->toArray(), $data['specs']);

        expect($data)->toHaveKeys(['type', 'entity', 'action', 'title', 'specs'])
            ->and($data['type'])->toBe('update')
            ->and($data['entity'])->toBe('test_model')
            ->and($data['action']['route'])->toBe('test_models.update')
            ->and($data['action']['params'])->toBe(1)
            ->and($specs)->toBeArray();
    });

    it('generates specs for simple form fields', function () {
        $form = FormService::make(TestModel::class, FormType::CREATE);
        $data = $form->jsonSerialize();
        $specs = array_map(fn ($spec) => $spec->toArray(), $data['specs']);

        expect($specs)->toBeArray()
            ->and($specs)->toHaveKey('name')
            ->and($specs['name'])->toBeArray()->toHaveKeys(['element', 'type', 'label', 'required'])
            ->and($specs['name']['element'])->toBe('input')
            ->and($specs['name']['type'])->toBe('text');
    });

    it('generates specs for complex mixed form', function () {
        $form = FormService::make(TestModel::class, FormType::CREATE);
        $data = $form->jsonSerialize();
        $specs = array_map(fn ($spec) => $spec->toArray(), $data['specs']);

        expect($specs)->toBeArray()
            ->and($data['type'])->toBe('create');
    });
});

describe('Form Title Generation', function () {
    it('generates CREATE title', function () {
        $form = FormService::make(TestModel::class, FormType::CREATE);
        $data = $form->jsonSerialize();

        expect($data['title'])->toBeString();
    });

    it('generates UPDATE title', function () {
        $model = new TestModel();
        $model->id = 1;
        $model->exists = true;
        $form = FormService::make($model, FormType::UPDATE);
        $data = $form->jsonSerialize();

        expect($data['title'])->toBeString();
    });
});
