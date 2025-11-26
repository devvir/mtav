<?php

// Copilot - Pending review

uses()->group('Unit.Services.FormService');

use App\Services\Form\Lib\Rule;
use Illuminate\Validation\Rule as LaravelRule;
use Illuminate\Validation\Rules\Enum;
use App\Enums\EventType;

describe('Rule Parsing - String Rules', function () {
    it('parses required rule', function () {
        $rule = Rule::make('name', 'required');

        expect($rule->required)->toBeTrue()
            ->and($rule->nullable)->toBeNull();
    });

    it('parses nullable rule', function () {
        $rule = Rule::make('description', 'nullable|string');

        expect($rule->nullable)->toBeTrue()
            ->and($rule->type)->toBe('string');
    });

    it('parses string type', function () {
        $rule = Rule::make('name', 'string');

        expect($rule->type)->toBe('string');
    });

    it('parses integer type', function () {
        $rule = Rule::make('age', 'integer');

        expect($rule->type)->toBe('integer');
    });

    it('parses int type', function () {
        $rule = Rule::make('count', 'int');

        expect($rule->type)->toBe('integer');
    });

    it('parses numeric type', function () {
        $rule = Rule::make('price', 'numeric');

        expect($rule->type)->toBe('numeric');
    });

    it('parses boolean type', function () {
        $rule = Rule::make('is_active', 'boolean');

        expect($rule->type)->toBe('boolean');
    });

    it('parses bool type', function () {
        $rule = Rule::make('is_active', 'bool');

        expect($rule->type)->toBe('boolean');
    });

    it('parses array type', function () {
        $rule = Rule::make('tags', 'array');

        expect($rule->type)->toBe('array');
    });

    it('parses date type', function () {
        $rule = Rule::make('published_at', 'date');

        expect($rule->type)->toBe('date');
    });

    it('parses email type', function () {
        $rule = Rule::make('email', 'email');

        expect($rule->type)->toBe('email');
    });

    it('parses url type', function () {
        $rule = Rule::make('website', 'url');

        expect($rule->type)->toBe('url');
    });

    it('parses file type', function () {
        $rule = Rule::make('document', 'file');

        expect($rule->type)->toBe('file');
    });

    it('parses image type', function () {
        $rule = Rule::make('photo', 'image');

        expect($rule->type)->toBe('image');
    });
});

describe('Rule Parsing - Constraints', function () {
    it('parses min constraint', function () {
        $rule = Rule::make('age', 'min:18');

        expect($rule->min)->toBe(18);
    });

    it('parses max constraint', function () {
        $rule = Rule::make('name', 'max:255');

        expect($rule->max)->toBe(255);
    });

    it('parses between constraint', function () {
        $rule = Rule::make('quantity', 'between:1,100');

        expect($rule->between)->toBe([1, 100]);
    });

    it('parses size constraint', function () {
        $rule = Rule::make('code', 'size:6');

        expect($rule->size)->toBe(6);
    });

    it('parses in constraint', function () {
        $rule = Rule::make('status', 'in:draft,published,archived');

        expect($rule->in)->toBe([
            'draft'     => 'draft',
            'published' => 'published',
            'archived'  => 'archived',
        ]);
    });
});

describe('Rule Parsing - Complex Rules', function () {
    it('parses combined pipe-delimited rules', function () {
        $rule = Rule::make('name', 'required|string|max:255');

        expect($rule->required)->toBeTrue()
            ->and($rule->type)->toBe('string')
            ->and($rule->max)->toBe(255);
    });

    it('parses array rules with objects', function () {
        $rule = Rule::make('project_id', ['required', LaravelRule::exists('projects', 'id')]);

        expect($rule->required)->toBeTrue()
            ->and($rule->exists)->toBe('App\Models\Project');
    });

    it('parses Enum rule objects', function () {
        $rule = Rule::make('event_type', ['required', new Enum(EventType::class)]);

        expect($rule->required)->toBeTrue()
            ->and($rule->in)->toBeArray()
            ->and($rule->in)->toHaveKey('lottery')
            ->and($rule->in)->toHaveKey('online')
            ->and($rule->in)->toHaveKey('onsite');
    });

    it('parses exists rule objects', function () {
        $rule = Rule::make('user_id', [LaravelRule::exists('users', 'id')]);

        expect($rule->exists)->toBe('App\Models\User');
    });
});

describe('Rule Parsing - Numeric Parameters', function () {
    it('parses integer parameters', function () {
        $rule = Rule::make('age', 'min:18|max:100');

        expect($rule->min)->toBe(18)
            ->and($rule->max)->toBe(100);
    });

    it('parses float parameters', function () {
        $rule = Rule::make('price', 'min:0.01|max:99999.99');

        expect($rule->min)->toBe(0.01)
            ->and($rule->max)->toBe(99999.99);
    });
});

describe('Rule Magic Getters', function () {
    it('returns null for undefined properties', function () {
        $rule = Rule::make('name', 'string');

        expect($rule->required)->toBeNull()
            ->and($rule->min)->toBeNull()
            ->and($rule->max)->toBeNull();
    });

    it('checks property existence with isset', function () {
        $rule = Rule::make('age', 'required|integer|min:18');

        expect(isset($rule->required))->toBeTrue()
            ->and(isset($rule->type))->toBeTrue()
            ->and(isset($rule->min))->toBeTrue()
            ->and(isset($rule->max))->toBeFalse();
    });
});

describe('Rule Array Conversion', function () {
    it('converts to array with filtered values', function () {
        $rule = Rule::make('name', 'required|string|max:255');
        $array = $rule->toArray();

        expect($array)->toBeArray()
            ->and($array)->toHaveKeys(['required', 'type', 'max'])
            ->and($array['required'])->toBeTrue()
            ->and($array['type'])->toBe('string')
            ->and($array['max'])->toBe(255);
    });
});

describe('Wildcard Rules', function () {
    it('stores wildcard rules for array fields', function () {
        $rule = Rule::make('tags', 'array', 'string|max:50');

        expect($rule->getWildcardRules())->toBe(['string', 'max:50']);
    });

    it('stores complex wildcard rules', function () {
        $rule = Rule::make('user_ids', 'array', [LaravelRule::exists('users', 'id')]);

        expect($rule->getWildcardRules())->toBeArray()
            ->and($rule->getWildcardRules()[0])->toBeInstanceOf(Illuminate\Validation\Rules\Exists::class);
    });
});
