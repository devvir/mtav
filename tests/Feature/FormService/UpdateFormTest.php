<?php

// Copilot - Pending review

use App\Services\Form\FormType;

uses()->group('Feature.FormService');

describe('AdminController::edit()', function () {
    it('returns correct Inertia form response structure', function () {
        $response = $this->visitRoute(['admins.edit', 11], asAdmin: 11);

        assertFormGeneration($response, FormType::UPDATE, 'admin', entityId: 11, specs: [
            'email' => [
                'element'  => 'input',
                'label'    => 'Email',
                'max'      => 255,
                'required' => true,
                'type'     => 'email',
                'value'    => 'admin11@example.com',
            ],
            'firstname' => [
                'element'  => 'input',
                'label'    => 'Firstname',
                'max'      => 80,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => 'Admin',
            ],
            'lastname' => [
                'element'  => 'input',
                'label'    => 'Lastname',
                'max'      => 80,
                'min'      => 2,
                'required' => false,
                'type'     => 'text',
                'value'    => '11 (manages 1)',
            ],
        ]);
    });
});

describe('ProjectController::edit()', function () {
    it('returns correct Inertia form response structure', function () {
        $response = $this->visitRoute(['projects.edit', 1], asAdmin: 1);

        assertFormGeneration($response, FormType::UPDATE, 'project', entityId: 1, specs: [
            'description' => [
                'element'  => 'input',
                'label'    => 'Description',
                'max'      => 500,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => 'Description 1',
            ],
            'name' => [
                'element'  => 'input',
                'label'    => 'Name',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => 'Project 1',
            ],
            'organization' => [
                'element'  => 'input',
                'label'    => 'Organization',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => 'Organization 1',
            ],
        ]);
    });
});

describe('FamilyController::edit()', function () {
    it('returns correct Inertia form response for admin', function () {
        $response = $this->visitRoute(['families.edit', 1], asAdmin: 11);

        assertFormGeneration($response, FormType::UPDATE, 'family', entityId: 1, specs: [
            'name' => [
                'element'  => 'input',
                'label'    => 'Name',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => 'Family 1 (no members)',
            ],
            'project_id' => [
                'element'  => 'select',
                'hidden'   => true,
                'label'    => 'Project',
                'multiple' => false,
                'options'  => [
                    1 => 'Project 1',
                ],
                'required' => true,
                'selected' => 1,
            ],
            'unit_type_id' => [
                'element'    => 'select',
                'filteredBy' => 'project_id',
                'label'      => 'Unit Type',
                'multiple'   => false,
                'options'    => [
                    1 => [
                        1 => 'Type 1',
                        2 => 'Type 2',
                        3 => 'Type 3',
                    ],
                ],
                'required' => true,
                'selected' => 1,
            ],
        ]);
    });

    it('returns correct Inertia form response for member editing own family', function () {
        $response = $this->visitRoute(['families.edit', 4], asMember: 102);

        // Backend returns same form structure for members as admins
        // Member can only edit their own family (enforced by policy)
        assertFormGeneration($response, FormType::UPDATE, 'family', entityId: 4, specs: [
            'name' => [
                'element'  => 'input',
                'label'    => 'Name',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => 'Family 4',
            ],
            'project_id' => [
                'element'  => 'select',
                'hidden'   => true,
                'label'    => 'Project',
                'multiple' => false,
                'options'  => [
                    1 => 'Project 1',
                ],
                'required' => true,
                'selected' => 1,
            ],
            'unit_type_id' => [
                'element'    => 'select',
                'filteredBy' => 'project_id',
                'label'      => 'Unit Type',
                'multiple'   => false,
                'options'    => [
                    1 => [
                        1 => 'Type 1',
                        2 => 'Type 2',
                        3 => 'Type 3',
                    ],
                ],
                'required' => true,
                'selected' => 1,
            ],
        ]);
    });
});

describe('MemberController::edit()', function () {
    it('returns correct Inertia form response structure', function () {
        setFirstProjectAsCurrent();
        $response = $this->visitRoute(['members.edit', 102], asAdmin: 11);

        assertFormGeneration($response, FormType::UPDATE, 'member', entityId: 102, specs: [
            'email' => [
                'element'  => 'input',
                'label'    => 'Email',
                'max'      => 255,
                'required' => true,
                'type'     => 'email',
                'value'    => 'member102@example.com',
            ],
            'firstname' => [
                'element'  => 'input',
                'label'    => 'Firstname',
                'max'      => 80,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => 'Member',
            ],
            'lastname' => [
                'element'  => 'input',
                'label'    => 'Lastname',
                'max'      => 80,
                'min'      => 2,
                'required' => false,
                'type'     => 'text',
                'value'    => '102',
            ],
        ]);
    });
});

describe('UnitController::edit()', function () {
    it('returns correct Inertia form response structure', function () {
        $response = $this->visitRoute(['units.edit', 1], asAdmin: 11);

        assertFormGeneration($response, FormType::UPDATE, 'unit', entityId: 1, specs: [
            'identifier' => [
                'element'  => 'input',
                'label'    => 'Identifier',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => 'Unit 1, Type 2',
            ],
            'project_id' => [
                'element'  => 'select',
                'hidden'   => true,
                'label'    => 'Project',
                'multiple' => false,
                'options'  => [
                    1 => 'Project 1',
                ],
                'required' => true,
                'selected' => 1,
            ],
            'unit_type_id' => [
                'element'    => 'select',
                'filteredBy' => 'project_id',
                'label'      => 'Unit Type',
                'multiple'   => false,
                'options'    => [
                    1 => [
                        1 => 'Type 1',
                        2 => 'Type 2',
                        3 => 'Type 3',
                    ],
                ],
                'required' => true,
                'selected' => 2,
            ],
        ]);
    });
});

describe('UnitTypeController::edit()', function () {
    it('returns correct Inertia form response structure', function () {
        $response = $this->visitRoute(['unit_types.edit', 1], asAdmin: 11);

        assertFormGeneration($response, FormType::UPDATE, 'unit_type', entityId: 1, specs: [
            'description' => [
                'element'  => 'input',
                'label'    => 'Description',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => 'Description 1',
            ],
            'name' => [
                'element'  => 'input',
                'label'    => 'Name',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => 'Type 1',
            ],
        ]);
    });
});

describe('EventController::edit()', function () {
    it('returns correct Inertia form response for regular event', function () {
        $event = \App\Models\Event::find(2);
        $response = $this->visitRoute(['events.edit', 2], asAdmin: 11);

        assertFormGeneration($response, FormType::UPDATE, 'event', entityId: 2, specs: [
            'description' => [
                'element'  => 'input',
                'label'    => 'Description',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => 'Monthly community meeting via video call',
            ],
            'end_date' => [
                'element'  => 'input',
                'label'    => 'End Date',
                'required' => false,
                'type'     => 'datetime-local',
                'value'    => $event->end_date?->toISOString(),
            ],
            'is_published' => [
                'element'  => 'select',
                'label'    => 'Published',
                'multiple' => false,
                'options'  => [
                    '0' => 'No Publicado',
                    '1' => 'Publicado',
                ],
                'selected' => '1',
            ],
            'location' => [
                'element'  => 'input',
                'label'    => 'Location',
                'max'      => 255,
                'min'      => 2,
                'required' => false,
                'type'     => 'text',
                'value'    => 'https://meet.example.com/proj1',
            ],
            'project_id' => [
                'element'  => 'select',
                'hidden'   => true,
                'label'    => 'Project',
                'multiple' => false,
                'options'  => [
                    1 => 'Project 1',
                ],
                'required' => true,
                'selected' => 1,
            ],
            'start_date' => [
                'element'  => 'input',
                'label'    => 'Start Date',
                'required' => false,
                'type'     => 'datetime-local',
                'value'    => $event->start_date?->toISOString(),
            ],
            'title' => [
                'element'  => 'input',
                'label'    => 'Title',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => 'Online Community Meeting',
            ],
            'type' => [
                'element'  => 'select',
                'label'    => 'Type',
                'multiple' => false,
                'options'  => [
                    'online' => 'En línea',
                    'onsite' => 'Presencial',
                ],
                'required' => true,
                'selected' => 'online',
            ],
        ]);
    });

    it('returns correct Inertia form response for lottery event', function () {
        $event = \App\Models\Event::find(1);
        $response = $this->visitRoute(['events.edit', 1], asAdmin: 11);

        assertFormGeneration($response, FormType::UPDATE, 'event', entityId: 1, specs: [
            'description' => [
                'element'  => 'input',
                'label'    => 'Description',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => 'Unit assignment lottery for Project 1',
            ],
            'location' => [
                'element'  => 'input',
                'label'    => 'Location',
                'max'      => 255,
                'min'      => 2,
                'required' => false,
                'type'     => 'text',
                'value'    => null,
            ],
            'project_id' => [
                'element'  => 'select',
                'hidden'   => true,
                'label'    => 'Project',
                'multiple' => false,
                'options'  => [
                    1 => 'Project 1',
                ],
                'required' => true,
                'selected' => 1,
            ],
            'start_date' => [
                'element'  => 'input',
                'label'    => 'Start Date',
                'required' => false,
                'type'     => 'datetime-local',
                'value'    => $event->start_date?->toISOString(),
            ],
        ]);
    });
});
