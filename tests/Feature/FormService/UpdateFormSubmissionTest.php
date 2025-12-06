<?php

// Copilot - Pending review

use App\Models\Admin;
use App\Models\Event;
use App\Models\Family;
use App\Models\Member;
use App\Models\Unit;
use App\Models\UnitType;

uses()->group('Feature.FormService.Submission');

describe('Update Form Submissions', function () {
    it('accepts update with no changes for :entity', function (string $entity, int $entityId, string $editRoute, string $updateRoute) {
        // Get entity model
        $modelClass = match ($entity) {
            'admin'     => Admin::class,
            'family'    => Family::class,
            'member'    => Member::class,
            'unit'      => Unit::class,
            'unit_type' => UnitType::class,
            'event'     => Event::class,
        };

        // Fetch form specs
        $response = $this->visitRoute([$editRoute, $entityId], asAdmin: 11);
        $specs = extractFormSpecs($response);

        // Get original entity attributes (before update)
        $originalEntity = $modelClass::find($entityId);
        $originalAttributes = $originalEntity->getAttributes();

        // Build default form data (uses current values from specs)
        $defaultData = generateEmptyFormData($specs);

        // Submit and expect success with no changes
        $this->patch(route($updateRoute, $entityId), $defaultData)
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        // Verify entity unchanged (check all attributes from specs)
        $updatedEntity = $modelClass::find($entityId);
        $updatedAttributes = $updatedEntity->getAttributes();

        foreach ($specs as $fieldName => $spec) {
            if (isset($originalAttributes[$fieldName])) {
                expect($updatedAttributes[$fieldName])
                    ->toBe($originalAttributes[$fieldName], "Field '{$fieldName}' should remain unchanged");
            }
        }
    })->with([
        'admin'     => ['admin', 11, 'admins.edit', 'admins.update'],
        'family'    => ['family', 1, 'families.edit', 'families.update'],
        'unit'      => ['unit', 1, 'units.edit', 'units.update'],
        'unit_type' => ['unit_type', 1, 'unit_types.edit', 'unit_types.update'],
        'event'     => ['event', 1, 'events.edit', 'events.update'],
    ]);

    // Additional specific validation tests (not consolidated since they test specific edge cases)
    describe('Specific Update Tests', function () {
        it('updates admin with valid changes (self-edit)', function () {
            $response = $this->visitRoute(['admins.edit', 11], asAdmin: 11);
            $specs = extractFormSpecs($response);

            $updateData = [
                'email'     => 'updated-admin11@example.com',
                'firstname' => 'Updated',
                'lastname'  => 'Name',
            ];

            $this->patch(route('admins.update', 11), $updateData)
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            $admin = Admin::find(11);
            expect($admin->email)->toBe('updated-admin11@example.com')
                ->and($admin->firstname)->toBe('Updated');
        });

        it('updates family with valid changes', function () {
            $response = $this->visitRoute(['families.edit', 1], asAdmin: 11);
            $specs = extractFormSpecs($response);

            $updateData = generateEmptyFormData($specs);
            $updateData['name'] = 'Updated Family Name';

            $this->patch(route('families.update', 1), $updateData)
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            expect(Family::find(1)->name)->toBe('Updated Family Name');
        });

        it('updates unit with valid changes', function () {
            $response = $this->visitRoute(['units.edit', 1], asAdmin: 11);
            $specs = extractFormSpecs($response);

            $updateData = generateEmptyFormData($specs);
            $updateData['identifier'] = 'UPDATED-UNIT-1';

            $this->patch(route('units.update', 1), $updateData)
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            expect(Unit::find(1)->identifier)->toBe('UPDATED-UNIT-1');
        });

        it('updates unit type with valid changes', function () {
            $response = $this->visitRoute(['unit_types.edit', 1], asAdmin: 11);
            $specs = extractFormSpecs($response);

            $updateData = generateEmptyFormData($specs);
            $updateData['name'] = 'Updated Type Name';

            $this->patch(route('unit_types.update', 1), $updateData)
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            expect(UnitType::find(1)->name)->toBe('Updated Type Name');
        });

        it('rejects invalid email format for admin (self-edit)', function () {
            $response = $this->visitRoute(['admins.edit', 11], asAdmin: 11);
            $specs = extractFormSpecs($response);

            $invalidData = generateValidFormData($specs, ['email' => 'not-an-email']);

            $this->patch(route('admins.update', 11), $invalidData)
                ->assertSessionHasErrors('email');
        });

        it('rejects unauthorized unit_type_id for families', function () {
            $response = $this->visitRoute(['families.edit', 1], asAdmin: 11);
            $specs = extractFormSpecs($response);

            $invalidData = generateValidFormData($specs, ['unit_type_id' => 4]); // UnitType 4 is in project 2

            $this->patch(route('families.update', 1), $invalidData)
                ->assertSessionHasErrors('unit_type_id');
        });

        it('rejects unauthorized unit_type_id for units', function () {
            $response = $this->visitRoute(['units.edit', 1], asAdmin: 11);
            $specs = extractFormSpecs($response);

            $invalidData = generateValidFormData($specs, ['unit_type_id' => 4]); // UnitType 4 is in project 2

            $this->patch(route('units.update', 1), $invalidData)
                ->assertSessionHasErrors('unit_type_id');
        });
    });
});
