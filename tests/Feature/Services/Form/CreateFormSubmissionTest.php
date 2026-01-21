<?php

// Copilot - Pending review

use App\Models\Admin;
use App\Models\Event;
use App\Models\Family;
use App\Models\Member;
use App\Models\Unit;
use App\Models\UnitType;

uses()->group('Feature.Services.Form.Submission');

describe('Create Form Submissions', function () {
    it('rejects empty submission for :entity', function (string $entity, string $createRoute) {
        // Fetch form specs
        $response = $this->visitRoute($createRoute, asAdmin: 11);
        $specs = extractFormSpecs($response);

        // Build empty form data (null for fields without defaults, default value otherwise)
        $emptyData = generateEmptyFormData($specs);

        // Determine which fields should error (required fields without default values)
        $expectedErrors = [];
        foreach ($specs as $fieldName => $spec) {
            if (($spec['required'] ?? false) && ($spec['value'] ?? null) === null && ($spec['selected'] ?? null) === null) {
                $expectedErrors[] = $fieldName;
            }
        }

        // Submit and expect validation errors for required fields without defaults
        $storeRoute = str_replace('.create', '.store', $createRoute);
        $this->post(route($storeRoute), $emptyData)
            ->assertSessionHasErrors($expectedErrors);
    })->with([
        'admin'     => ['admin', 'admins.create'],
        'family'    => ['family', 'families.create'],
        'member'    => ['member', 'members.create'],
        'unit'      => ['unit', 'units.create'],
        'unit_type' => ['unit_type', 'unit_types.create'],
        'event'     => ['event', 'events.create'],
    ]);

    // Additional specific validation tests (not consolidated since they test specific edge cases)
    describe('Specific Validation Tests', function () {
        it('creates admin with valid data', function () {
            $response = $this->visitRoute('admins.create', asAdmin: 11);
            $specs = extractFormSpecs($response);
            $validData = generateValidFormData($specs, [
                'project_ids' => [1], // Admin #11 manages project #1
                'email'       => 'newadmin@example.com',
                'firstname'   => 'New',
                'lastname'    => 'Admin',
            ]);

            $this->post(route('admins.store'), $validData)
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            expect(Admin::where('email', 'newadmin@example.com')->exists())->toBeTrue();
        });

        it('creates family with valid data', function () {
            $response = $this->visitRoute('families.create', asAdmin: 11);
            $specs = extractFormSpecs($response);
            $validData = generateValidFormData($specs, [
                'name' => 'New Family',
            ]);

            $this->post(route('families.store'), $validData)
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            expect(Family::where('name', 'New Family')->exists())->toBeTrue();
        });

        it('creates member with valid data', function () {
            $response = $this->visitRoute('members.create', asAdmin: 11);
            $specs = extractFormSpecs($response);
            $validData = generateValidFormData($specs, [
                'email'     => 'newmember@example.com',
                'firstname' => 'New',
                'lastname'  => 'Member',
            ]);

            $this->post(route('members.store'), $validData)
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            expect(Member::where('email', 'newmember@example.com')->exists())->toBeTrue();
        });

        it('creates unit with valid data', function () {
            $response = $this->visitRoute('units.create', asAdmin: 11);
            $specs = extractFormSpecs($response);
            $validData = generateValidFormData($specs, [
                'identifier' => 'NEW-UNIT-101',
            ]);

            $this->post(route('units.store'), $validData)
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            expect(Unit::where('identifier', 'NEW-UNIT-101')->exists())->toBeTrue();
        });

        it('creates unit type with valid data', function () {
            $response = $this->visitRoute('unit_types.create', asAdmin: 11);
            $specs = extractFormSpecs($response);
            $validData = generateValidFormData($specs, [
                'name' => 'New Unit Type',
            ]);

            $this->post(route('unit_types.store'), $validData)
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            expect(UnitType::where('name', 'New Unit Type')->exists())->toBeTrue();
        });

        it('creates event with valid data', function () {
            $response = $this->visitRoute('events.create', asAdmin: 11);
            $specs = extractFormSpecs($response);
            $validData = generateValidFormData($specs, [
                'title'       => 'New Event',
                'description' => 'Event description here',
            ]);

            $this->post(route('events.store'), $validData)
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            expect(Event::where('title', 'New Event')->exists())->toBeTrue();
        });

        it('rejects invalid email format for admins', function () {
            $response = $this->visitRoute('admins.create', asAdmin: 11);
            $specs = extractFormSpecs($response);
            $invalidData = generateValidFormData($specs, [
                'email' => 'not-an-email',
            ]);

            $this->post(route('admins.store'), $invalidData)
                ->assertSessionHasErrors('email');
        });

        it('rejects unauthorized unit_type_id for units', function () {
            $response = $this->visitRoute('units.create', asAdmin: 11);
            $specs = extractFormSpecs($response);

            // UnitType #4 is in project 2, which admin 11 doesn't manage
            $validData = generateValidFormData($specs, ['unit_type_id' => 4]);

            $this->post(route('units.store'), $validData)
                ->assertSessionHasErrors('unit_type_id');
        });

        it('rejects invalid event type', function () {
            $response = $this->visitRoute('events.create', asAdmin: 11);
            $specs = extractFormSpecs($response);

            $validData = generateValidFormData($specs, ['type' => 'invalid_type']);

            $this->post(route('events.store'), $validData)
                ->assertSessionHasErrors('type');
        });
    });
});
