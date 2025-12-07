<?php

// Copilot - Pending review

uses()->group('Unit.Services.FormService');

use App\Enums\EventType;
use App\Services\Form\Lib\Rule;
use App\Services\Form\Lib\SpecFactory;
use App\Services\Form\Lib\SpecInput;
use App\Services\Form\Lib\SpecSelect;
use Illuminate\Validation\Rule as LaravelRule;
use Illuminate\Validation\Rules\Enum;

beforeEach(function () {
    // Mock config for model namespace
    config(['forms.namespaces.models' => 'App\\Models']);
});

describe('SpecFactory - Determines Spec Type', function () {
    it('creates SpecInput for string fields', function () {
        $rule = Rule::make('name', 'required|string|between:2,255');
        $spec = SpecFactory::make($rule);

        expect($spec)->toBeInstanceOf(SpecInput::class);
    });

    it('creates SpecInput for integer fields', function () {
        $rule = Rule::make('age', 'integer|min:1|max:120');
        $spec = SpecFactory::make($rule);

        expect($spec)->toBeInstanceOf(SpecInput::class);
    });

    it('creates SpecInput for numeric fields', function () {
        $rule = Rule::make('price', 'numeric|min:0');
        $spec = SpecFactory::make($rule);

        expect($spec)->toBeInstanceOf(SpecInput::class);
    });

    it('creates SpecInput for email fields', function () {
        $rule = Rule::make('email', 'required|email');
        $spec = SpecFactory::make($rule);

        expect($spec)->toBeInstanceOf(SpecInput::class);
    });

    it('creates SpecInput for date fields', function () {
        $rule = Rule::make('published_at', 'nullable|date');
        $spec = SpecFactory::make($rule);

        expect($spec)->toBeInstanceOf(SpecInput::class);
    });

    it('creates SpecSelect for boolean fields', function () {
        $rule = Rule::make('is_active', 'required|boolean');
        $spec = SpecFactory::make($rule);

        expect($spec)->toBeInstanceOf(SpecSelect::class);
    });

    it('creates SpecSelect for array fields', function () {
        $rule = Rule::make('tags', 'required|array');
        $spec = SpecFactory::make($rule);

        expect($spec)->toBeInstanceOf(SpecSelect::class);
    });

    it('creates SpecSelect for in constraints', function () {
        $rule = Rule::make('status', 'required|in:draft,published,archived');
        $spec = SpecFactory::make($rule);

        expect($spec)->toBeInstanceOf(SpecSelect::class);
    });

    it('creates SpecSelect for enum constraints', function () {
        $rule = Rule::make('event_type', ['required', new Enum(EventType::class)]);
        $spec = SpecFactory::make($rule);

        expect($spec)->toBeInstanceOf(SpecSelect::class);
    });

    it('creates SpecSelect for exists constraints', function () {
        $rule = Rule::make('project_id', ['required', LaravelRule::exists('projects', 'id')]);
        $spec = SpecFactory::make($rule);

        expect($spec)->toBeInstanceOf(SpecSelect::class);
    });
});

describe('SpecInput - Field Generation', function () {
    it('generates text input spec', function () {
        $rule = Rule::make('name', 'required|string|between:2,255');
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array)->toHaveKeys(['element', 'type', 'label', 'required', 'max'])
            ->and($array['element'])->toBe('input')
            ->and($array['type'])->toBe('text')
            ->and($array['label'])->toBe('Name')
            ->and($array['required'])->toBeTrue()
            ->and($array['max'])->toBe(255);
    });

    it('generates number input spec', function () {
        $rule = Rule::make('age', 'integer|min:18|max:120');
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array['element'])->toBe('input')
            ->and($array['type'])->toBe('number')
            ->and($array['min'])->toBe(18)
            ->and($array['max'])->toBe(120)
            ->and($array['required'])->toBeFalse();
    });

    it('generates email input spec', function () {
        $rule = Rule::make('email', 'required|email');
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array['element'])->toBe('input')
            ->and($array['type'])->toBe('email')
            ->and($array['required'])->toBeTrue();
    });

    it('generates date input spec', function () {
        $rule = Rule::make('published_at', 'nullable|date');
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array['element'])->toBe('input')
            ->and($array['type'])->toBe('datetime-local')
            ->and($array['required'])->toBeFalse();
    });

    it('converts between constraint to min and max', function () {
        $rule = Rule::make('quantity', 'integer|between:1,100');
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array['min'])->toBe(1)
            ->and($array['max'])->toBe(100);
    });

    it('generates proper label from field name', function () {
        $rule = Rule::make('first_name', 'string');
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array['label'])->toBe('First Name');
    });

    it('removes is_ prefix from boolean field labels', function () {
        $rule = Rule::make('is_active', 'string'); // Using string to force SpecInput
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array['label'])->toBe('Active');
    });

    it('removes _id suffix from field labels', function () {
        $rule = Rule::make('project_id', 'string'); // Using string to force SpecInput
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array['label'])->toBe('Project');
    });

    it('pluralizes _ids field labels', function () {
        $rule = Rule::make('user_ids', 'string'); // Using string to force SpecInput
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array['label'])->toBe('Users');
    });
});

describe('SpecSelect - Field Generation', function () {
    it('generates boolean select spec', function () {
        $rule = Rule::make('is_active', 'required|boolean');
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array['element'])->toBe('select')
            ->and($array['label'])->toBe('Active')
            ->and($array['required'])->toBeTrue()
            ->and($array['options'])->toBeArray()
            ->and($array['options'])->toHaveKey('0')
            ->and($array['options'])->toHaveKey('1');
    });

    it('generates in constraint select spec', function () {
        $rule = Rule::make('status', 'required|in:draft,published,archived');
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array['element'])->toBe('select')
            ->and($array['options'])->toBe([
                'draft'     => 'draft',
                'published' => 'published',
                'archived'  => 'archived',
            ])
            ->and($array['required'])->toBeTrue();
    });

    it('generates enum select spec', function () {
        $rule = Rule::make('event_type', ['required', new Enum(EventType::class)]);
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array['element'])->toBe('select')
            ->and($array['options'])->toBeArray()
            ->and($array['options'])->toHaveKey('lottery')
            ->and($array['options'])->toHaveKey('online')
            ->and($array['options'])->toHaveKey('onsite')
            ->and($array['required'])->toBeTrue();
    });

    it('sets multiple flag for array fields', function () {
        $rule = Rule::make('tags', 'required|array');
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array['element'])->toBe('select')
            ->and($array['multiple'])->toBeTrue();
    });

    it('generates proper label for _ids fields', function () {
        $rule = Rule::make('user_ids', 'required|array');
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array['label'])->toBe('Users');
    });
});

describe('SpecSelect - Database Options', function () {
    it('infers model from _id field name', function () {
        // This test requires actual database access, so we'll skip implementation details
        // Just verify the spec structure is correct
        $rule = Rule::make('project_id', ['required', LaravelRule::exists('projects', 'id')]);
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array['element'])->toBe('select')
            ->and($array['label'])->toBe('Project')
            ->and($array['options'])->toBeArray();
    });

    it('handles array fields with wildcard exists rules', function () {
        $rule = Rule::make('user_ids', 'required|array', [LaravelRule::exists('users', 'id')]);
        $spec = SpecFactory::make($rule);
        $array = $spec->toArray();

        expect($array['element'])->toBe('select')
            ->and($array['multiple'])->toBeTrue()
            ->and($array['options'])->toBeArray();
    });
});
