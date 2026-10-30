<?php

use App\Models\Activity;

/**
 * Everything that changes on the platform, and every way in, is written down for its
 * superadmins: who, what, on which project, from what to what. A person's own details
 * only by name; secrets never.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->type = aUnitType($this->project);
});

it('writes down who changed what on which project, from what to what', function () {
    $unit = aUnit($this->type, ['identifier' => 'A-101']);

    $this->as($this->admin, $this->project)
        ->put(route('manage.units.update', [$this->project, $unit]), ['identifier' => 'A-102', 'unit_type_id' => $this->type->id])
        ->assertSessionHasNoErrors();

    $entry = Activity::where('action', 'updated')->where('subject_type', 'unit')->sole();
    expect($entry)
        ->user_id->toBe($this->admin->id)
        ->actor->toBe($this->admin->name)
        ->project_id->toBe($this->project->id)
        ->subject->toBe('A-102')
        ->and($entry->changes['identifier'])->toBe(['A-101', 'A-102']);
});

it('notes a person\'s own changes by field only, and never a secret', function () {
    $person = aUser(['phone' => '099 000 000']);
    $person->update(['phone' => '099 111 111', 'password' => 'another-Pass-1']);

    $entry = Activity::where('subject_type', 'user')->where('action', 'updated')->latest('id')->first();
    expect($entry->changes)->toBe(['phone' => null]);
});

it('writes down roles given, with their values', function () {
    $person = aUser();
    $person->forceFill(['is_superadmin' => true])->save();

    expect(Activity::where('subject_type', 'user')->where('action', 'updated')->latest('id')->first()->changes)
        ->toBe(['is_superadmin' => [false, true]]);
});

it('writes down signing in, and failing to', function () {
    $person = aUser(['email' => 'ana@example.com', 'password' => 'the-Right-pass-1']);

    $this->post(route('login'), ['email' => 'ana@example.com', 'password' => 'a-wrong-one']);
    $this->post(route('login'), ['email' => 'ana@example.com', 'password' => 'the-Right-pass-1']);

    expect(Activity::where('action', 'sign_in_failed')->sole()->changes)->toBe(['email' => 'ana@example.com'])
        ->and(Activity::where('action', 'signed_in')->sole()->user_id)->toBe($person->id);
});

it('keeps who did it after their account is gone', function () {
    aUnit($this->type, ['identifier' => 'B-1']);
    $this->as($this->admin, $this->project)->post(route('manage.unit_types.store', $this->project), ['name' => 'Studio']);
    $name = $this->admin->name;
    App\Models\Membership::where('user_id', $this->admin->id)->delete();
    $this->admin->delete();

    expect(Activity::where('subject', 'Studio')->sole())->user_id->toBeNull()->actor->toBe($name);
});

it('shows superadmins the activity, by project and kind, and no one else', function () {
    aUnit($this->type, ['identifier' => 'C-1']);
    $other = aProject();
    aUnit(aUnitType($other), ['identifier' => 'Z-9']);

    $page = $this->as(aSuperadmin())->get(route('admin.activity.index', ['project' => $this->project->id, 'kind' => 'changes']));
    $subjects = collect($page->inertiaProps('activity.data'))->pluck('subject');

    expect($subjects)->toContain('C-1')->not->toContain('Z-9');
    expect($this->as($this->admin)->get(route('admin.activity.index')))->toBeDenied();
});
