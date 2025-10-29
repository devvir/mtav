<?php

use App\Models\Unit;

describe('Unit CRUD - Index/Show (All Users)', function () {


});

describe('Unit CRUD - Create/Store (Admin Only)', function () {





});

describe('Unit CRUD - Update (Admin Only)', function () {



});

describe('Unit CRUD - Delete (Admin Only)', function () {
    it('allows admin to delete unit in project they manage', function () {
        $admin = createAdmin(asUser: true);
        $project = createProject();
        $project->addAdmin($admin->asAdmin());
        $unit = Unit::factory()->create(['project_id' => $project->id]);

        $response = inertiaDelete($admin, route('units.destroy', $unit));

        expect(Unit::withTrashed()->find($unit->id)->deleted_at)->not->toBeNull();
        $response->assertRedirect();
    });



});

describe('Unit CRUD - Project Scope Enforcement', function () {

});
