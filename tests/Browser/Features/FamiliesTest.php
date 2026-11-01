<?php

use App\Models\FamilyJoinRequest;
use App\Models\User;

/**
 * Finding one's family: guests from the menu; those in one, quietly, from their family's card.
 */
beforeEach(function () {
    $this->project = aProject(['allows_guests' => true]);
    $this->type = aUnitType($this->project);
    $this->family = aFamily($this->project, $this->type, ['name' => 'Pereira']);
    $this->member = aMemberOf($this->family, ['firstname' => 'María']);
    $this->guest = User::factory()->create(['firstname' => 'Ana']);
    $this->project->memberships()->create(['user_id' => $this->guest->id]);
});

it('lets a guest join their family from the menu, and its people accept them', function () {
    $this->as($this->guest);

    visit(route('projects.show', $this->project))
        ->click('a:has-text("Join your family")')
        ->assertSee('Find your family')
        ->assertSee('María')
        ->click('.family-option button:has-text("Ask to join")')
        ->assertSee('Waiting for the Pereira family to answer')
        ->assertNoSmoke();

    expect(FamilyJoinRequest::where('user_id', $this->guest->id)->exists())->toBeTrue();

    $this->as($this->member);

    visit(route('families.index', ['project' => $this->project, 'family' => $this->family->id]))
        ->assertSeeIn('.family-requests', 'asks to join your family')
        ->click('.family-requests button:has-text("Accept")')
        ->assertNoSmoke();

    expect($this->guest->fresh()->familyIn($this->project)->id)->toBe($this->family->id);
});

it('keeps changing family out of the way, on one\'s own family\'s card', function () {
    $this->as($this->member);

    visit(route('projects.show', $this->project))
        ->assertDontSee('Join your family')
        ->assertNoSmoke();

    visit(route('families.index', ['project' => $this->project, 'family' => $this->family->id]))
        ->click('.family-wrong')
        ->assertSee('In the wrong family?')
        ->assertNoSmoke();
});

it('says why a family that received a unit cannot be removed, to the pointer and the keyboard', function () {
    aUnit($this->type, ['family_id' => $this->family->id, 'identifier' => 'A-101']);
    $this->as(anAdminOf($this->project));

    $page = visit(route('families.index', ['project' => $this->project, 'family' => $this->family->id]))
        ->assertPresent('[role="dialog"] button:has-text("Remove")');

    expect($page->script('() => [...document.querySelectorAll("[role=\"dialog\"] button")].find((button) => button.textContent.trim() === "Remove").getAttribute("aria-disabled")'))->toBe('true');

    $page->keys('[role="dialog"] button:has-text("Remove")', 'Shift')->assertSee('It already received a unit');
    $page->script('() => [...document.querySelectorAll("[role=\"dialog\"] button")].find((button) => button.textContent.trim() === "Remove").click()');

    $page->wait(0.3)->assertDontSee('Remove the Pereira family?');
    expect($this->family->fresh())->not->toBeNull();
});

it('says why a family with people cannot be removed', function () {
    $this->as(anAdminOf($this->project));

    $page = visit(route('families.index', ['project' => $this->project, 'family' => $this->family->id]))
        ->assertPresent('[role="dialog"] button:has-text("Remove")');

    $page->keys('[role="dialog"] button:has-text("Remove")', 'Shift')->assertSee('It still has people: move or remove them first');
    $page->script('() => [...document.querySelectorAll("[role=\"dialog\"] button")].find((button) => button.textContent.trim() === "Remove").click()');

    $page->wait(0.3)->assertDontSee('Remove the Pereira family?');
    expect($this->family->fresh()->trashed())->toBeFalse();
});

it('shows an archived family\'s history, and deletes it for good from there', function () {
    $admin = anAdminOf($this->project);
    $archived = aFamily($this->project, $this->type, ['name' => 'Ferreira']);
    aMemberOf($archived, ['firstname' => 'Lucía', 'lastname' => 'Rey']);
    $archived->memberships()->sole()->update(['family_id' => null]);

    $this->as($admin);

    visit(route('families.index', [$this->project, 'archived' => 1]))
        ->assertSee('Ferreira')
        ->click(button('History'))
        ->assertSee('Who was in it')
        ->assertSee('Lucía Rey')
        ->click(inDialog('Delete for good'))
        ->assertSee('Delete the Ferreira family for good?')
        ->click(inDialog('Delete for good'))
        ->assertSee('deleted for good')
        ->assertDontSee('Lucía Rey')
        ->assertNoSmoke();

    expect($archived->fresh())->toBeNull();
});
