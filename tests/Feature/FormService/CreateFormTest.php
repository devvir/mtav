<?php

// Copilot - Pending review

use App\Services\Form\FormType;

uses()->group('Feature.FormService');

describe('AdminController::create()', function () {
    it('returns correct Inertia form response', function () {
        $response = $this->visitRoute('admins.create', asAdmin: 11);

        assertFormGeneration($response, FormType::CREATE, 'admin', specs: [
            'email' => [
                'element'  => 'input',
                'label'    => 'Email',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'email',
                'value'    => null,
            ],
            'firstname' => [
                'element'  => 'input',
                'label'    => 'Firstname',
                'max'      => 80,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => null,
            ],
            'lastname' => [
                'element'  => 'input',
                'label'    => 'Lastname',
                'max'      => 80,
                'min'      => 2,
                'required' => false,
                'type'     => 'text',
                'value'    => null,
            ],
            'project_ids' => [
                'element'  => 'select',
                'hidden'   => true,
                'label'    => 'Projects',
                'multiple' => true,
                'options'  => [
                    1 => 'Project 1',
                ],
                'required' => true,
                'selected' => null,
            ],
        ]);
    });
});

describe('ProjectController::create()', function () {
    it('returns correct Inertia form response', function () {
        $response = $this->visitRoute('projects.create', asAdmin: 1);

        assertFormGeneration($response, FormType::CREATE, 'project', specs: [
            'name' => [
                'element'  => 'input',
                'label'    => 'Name',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => null,
            ],
            'description' => [
                'element'  => 'input',
                'label'    => 'Description',
                'max'      => 500,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => null,
            ],
            'organization' => [
                'element'  => 'input',
                'label'    => 'Organization',
                'max'      => 255,
                'min'      => 2,
                'required' => false,
                'type'     => 'text',
                'value'    => null,
            ],
            'new_admin_email' => [
                'element'  => 'input',
                'label'    => 'New Admin Email',
                'max'      => 255,
                'min'      => 2,
                'required' => false,
                'type'     => 'email',
                'value'    => null,
            ],
            'new_admin_firstname' => [
                'element'  => 'input',
                'label'    => 'New Admin Firstname',
                'max'      => 80,
                'min'      => 2,
                'required' => false,
                'type'     => 'text',
                'value'    => null,
            ],
            'new_admin_lastname' => [
                'element'  => 'input',
                'label'    => 'New Admin Lastname',
                'max'      => 80,
                'min'      => 2,
                'required' => false,
                'type'     => 'text',
                'value'    => null,
            ],
            'admins' => [
                'element'  => 'select',
                'label'    => 'Admins',
                'multiple' => true,
                'options'  => [
                    1  => 'superadmin 1 (no projects)',
                    10 => 'Admin 10 (no projects)',
                    11 => 'Admin 11 (manages 1)',
                    12 => 'Admin 12 (manages 2,3)',
                    13 => 'Admin 13 (manages 2-,3+,4+)',
                    14 => 'Admin 14 (manages deleted 5)',
                    15 => 'Admin 15 (manages 2, deleted 5)',
                    16 => 'Admin 16 (manages 2,3,4, deleted 5)',
                    17 => 'admin (unverified) 17 (no projects)',
                    18 => 'admin (invited) 18 (manages 1)',
                    19 => 'admin (invited) 19 (manages 1,2)',
                ],
                'selected' => null,
            ],
        ]);
    });
});

describe('FamilyController::create()', function () {
    it('returns correct Inertia form response for project scope', function () {
        setFirstProjectAsCurrent();
        $response = $this->visitRoute('families.create', asAdmin: 11);

        assertFormGeneration($response, FormType::CREATE, 'family', specs: [
            'name' => [
                'element'  => 'input',
                'label'    => 'Name',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
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
                'selected' => null,
            ],
        ]);
    });
});

describe('MemberController::create()', function () {
    it('returns correct Inertia form response for admin', function () {
        setFirstProjectAsCurrent();
        $response = $this->visitRoute('members.create', asAdmin: 11);

        assertFormGeneration($response, FormType::CREATE, 'member', specs: [
            'email' => [
                'element'  => 'input',
                'label'    => 'Email',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'email',
                'value'    => null,
            ],
            'family_id' => [
                'element'    => 'select',
                'filteredBy' => 'project_id',
                'label'      => 'Family',
                'multiple'   => false,
                'options'    => [
                    1 => [
                        1  => 'Family 1 (no members)',
                        2  => 'Family 2 (inactive member)',
                        3  => 'Family 3 (deleted member)',
                        4  => 'Family 4',
                        5  => 'Family 5',
                        6  => 'Family 6',
                        7  => 'Family 7',
                        8  => 'Family 8',
                        9  => 'Family 9',
                        10 => 'Family 10',
                        11 => 'Family 11',
                        12 => 'Family 12',
                        24 => 'Family 24 (unverified member)',
                        25 => 'Family 25 (invited member)',
                    ],
                ],
                'required' => true,
                'selected' => null,
            ],
            'firstname' => [
                'element'  => 'input',
                'label'    => 'Firstname',
                'max'      => 80,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => null,
            ],
            'lastname' => [
                'element'  => 'input',
                'label'    => 'Lastname',
                'max'      => 80,
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
        ]);
    });

    it('returns correct Inertia form response for member (frontend hides fields)', function () {
        $response = $this->visitRoute('members.create', asMember: 102);

        // Backend returns same form for members as admins
        // Frontend is responsible for hiding project_id and family_id fields
        assertFormGeneration($response, FormType::CREATE, 'member', specs: [
            'email' => [
                'element'  => 'input',
                'label'    => 'Email',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'email',
                'value'    => null,
            ],
            'family_id' => [
                'element'    => 'select',
                'filteredBy' => 'project_id',
                'label'      => 'Family',
                'multiple'   => false,
                'options'    => [
                    1 => [
                        1  => 'Family 1 (no members)',
                        2  => 'Family 2 (inactive member)',
                        3  => 'Family 3 (deleted member)',
                        4  => 'Family 4',
                        5  => 'Family 5',
                        6  => 'Family 6',
                        7  => 'Family 7',
                        8  => 'Family 8',
                        9  => 'Family 9',
                        10 => 'Family 10',
                        11 => 'Family 11',
                        12 => 'Family 12',
                        24 => 'Family 24 (unverified member)',
                        25 => 'Family 25 (invited member)',
                    ],
                ],
                'required' => true,
                'selected' => null,
            ],
            'firstname' => [
                'element'  => 'input',
                'label'    => 'Firstname',
                'max'      => 80,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => null,
            ],
            'lastname' => [
                'element'  => 'input',
                'label'    => 'Lastname',
                'max'      => 80,
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
        ]);
    });
});

describe('UnitController::create()', function () {
    it('returns correct Inertia form response for project scope', function () {
        setFirstProjectAsCurrent();
        $response = $this->visitRoute('units.create', asAdmin: 11);

        assertFormGeneration($response, FormType::CREATE, 'unit', specs: [
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
                'selected' => null,
            ],
            'identifier' => [
                'element'  => 'input',
                'label'    => 'Identifier',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => null,
            ],
        ]);
    });
});

describe('UnitTypeController::create()', function () {
    it('returns correct Inertia form response for project scope', function () {
        setFirstProjectAsCurrent();
        $response = $this->visitRoute('unit_types.create', asAdmin: 11);

        assertFormGeneration($response, FormType::CREATE, 'unit_type', specs: [
            'description' => [
                'element'  => 'input',
                'label'    => 'Description',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => null,
            ],
            'name' => [
                'element'  => 'input',
                'label'    => 'Name',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => null,
            ],
        ]);
    });
});

describe('EventController::create()', function () {
    it('returns correct Inertia form response for project scope', function () {
        setFirstProjectAsCurrent();
        $response = $this->visitRoute('events.create', asAdmin: 11);

        assertFormGeneration($response, FormType::CREATE, 'event', specs: [
            'description' => [
                'element'  => 'input',
                'label'    => 'Description',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => null,
            ],
            'end_date' => [
                'element'  => 'input',
                'label'    => 'End Date',
                'required' => false,
                'type'     => 'datetime-local',
                'value'    => null,
            ],
            'is_published' => [
                'element'  => 'select',
                'label'    => 'Published',
                'multiple' => false,
                'options'  => [
                    '0' => 'No Publicado',
                    '1' => 'Publicado',
                ],
                'selected' => null,
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
                'value'    => null,
            ],
            'title' => [
                'element'  => 'input',
                'label'    => 'Title',
                'max'      => 255,
                'min'      => 2,
                'required' => true,
                'type'     => 'text',
                'value'    => null,
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
                'selected' => null,
            ],
        ]);
    });
});
