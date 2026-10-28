<?php

use App\Models\Project;
use App\Models\User;

/**
 * The platform console, for superadmins: every account, and who else manages the platform.
 */

beforeEach(function () {
    $this->superadmin = aSuperadmin();
    $this->as($this->superadmin);
});

it('gives the superadmin role from its own page, by exact email, and takes it back', function () {
    $ana = aUser(['firstname' => 'Ana', 'lastname' => 'García', 'email' => 'ana@example.com']);

    // Never offered on the accounts themselves
    visit(route('admin.users.index'))
        ->assertSee('Ana García')
        ->assertDontSee('Make superadmin');

    visit(route('admin.superadmins.index'))
        ->fill('email', 'ana@example.com')
        ->click('Give the superadmin role')
        ->fill('[role="dialog"] input', 'ana@example.com')
        ->click(inDialog('Give the role'))
        ->assertSee('Ana García');

    expect($ana->fresh()->isSuperadmin())->toBeTrue();

    visit(route('admin.superadmins.index'))
        ->click('.row:has-text("Ana García") >> text=Remove the role')
        ->click(inDialog('Remove the role'))
        ->assertDontSee('ana@example.com');

    expect($ana->fresh()->isSuperadmin())->toBeFalse();
});

it('finds accounts by name or email', function () {
    aUser(['firstname' => 'Ana', 'lastname' => 'García', 'email' => 'ana@example.com']);
    aUser(['firstname' => 'Bruno', 'lastname' => 'Pérez', 'email' => 'bruno@example.com']);

    visit(route('admin.users.index'))
        ->type('[aria-label="Search by name or email"]', 'bruno@')
        ->assertSee('Bruno Pérez')
        ->assertDontSee('Ana García')
        ->assertQueryStringHas('q', 'bruno@');
});

it('pages through long lists', function () {
    User::factory()->count(45)->create();

    visit(route('admin.users.index'))
        ->click('[aria-label="Next page"]')
        ->assertQueryStringHas('page', '2')
        ->click('[aria-label="Previous page"]')
        ->assertQueryStringHas('page', '1');
});

it('previews the components in either scheme', function () {
    visit(route('admin.lab'))
        ->click('.lab-head >> text=Dark')
        ->assertScript("document.documentElement.getAttribute('data-mantine-color-scheme')", 'dark')
        ->click('.lab-head >> text=Light')
        ->assertScript("document.documentElement.getAttribute('data-mantine-color-scheme')", 'light');
});

it('restores an archived project, or deletes it for good after its summary and its name', function () {
    $kept = aProject(['name' => 'Cooperativa Vuelta']);
    $gone = aProject(['name' => 'Cooperativa Final']);
    aMemberOf(aFamily($kept));
    aMemberOf(aFamily($gone));
    $kept->delete();
    $gone->delete();

    $page = visit(route('admin.projects.index', ['show' => 'archived']))
        ->assertSee('Cooperativa Vuelta')
        ->assertSee('Cooperativa Final');

    $page->click('.row:has-text("Cooperativa Vuelta") >> ' . button('Restore'))
        ->click(inDialog('Restore'))
        ->assertSee('Cooperativa Vuelta is back, as it was.');

    $page->click('.row:has-text("Cooperativa Final") >> ' . button('Delete for good'))
        ->assertSee('Delete Cooperativa Final for good?')
        ->assertSee('Lottery runs')
        ->assertSee('Everything in it is deleted, its files too.');

    // Not before the name is typed
    expect($page->script('[...document.querySelectorAll(\'[role="dialog"] button\')].find((b) => b.textContent.includes("Delete for good")).disabled'))->toBeTrue();

    $page->fill('[role="dialog"] input', 'Cooperativa Final')
        ->click(inDialog('Delete for good'))
        ->assertSee('Cooperativa Final was deleted for good.')
        ->assertNoSmoke();

    expect(Project::withTrashed()->find($gone->id))->toBeNull();
    expect(Project::find($kept->id))->not->toBeNull();
});
