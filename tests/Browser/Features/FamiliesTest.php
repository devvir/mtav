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
