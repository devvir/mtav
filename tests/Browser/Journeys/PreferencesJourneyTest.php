<?php

use App\Models\Event;
use App\Models\Family;
use App\Models\User;

uses()->group('Browser.Journeys');

/**
 * Journey 6 — Preferences drive the lottery outcome.
 *
 * The domain core: a member ranks their family's unit preferences through the
 * member UI, an admin runs the lottery, and the assignment honors what was
 * ranked. Uses project 2, unit type 4 (units 4 & 5, families 13 & 14) so two
 * families compete with distinct top choices — a deterministic GLPK outcome.
 */
test('a member ranks preferences and the lottery honors them', function () {
    config()->set('app.locale', 'en');

    $family13 = Family::findOrFail(13);
    $family14 = Family::findOrFail(14);

    // Seed a known starting order so the reorder below is deterministic
    $family13->preferences()->sync([4 => ['order' => 1], 5 => ['order' => 2]]);
    // Family 14 prefers Unit 4 — distinct top choice from what family 13 will pick
    $family14->preferences()->sync([4 => ['order' => 1], 5 => ['order' => 2]]);

    // --- Member #136 (family 13) reorders their preferences ---
    $this->actingAs(User::findOrFail(136));
    setCurrentProject(2);

    visit(route('lottery'))
        ->waitForText('Unit Preferences')
        ->assertSee('Unit 4, Type 4')
        ->assertSee('Unit 5, Type 4')
        // Move the first-ranked unit (Unit 4) down → order becomes [Unit 5, Unit 4]
        ->click('button[aria-label="Move down"]:not([disabled]):visible')
        ->wait(2) // preferences save async with no flash; let the POST land
        ->assertNoSmoke();

    expect(Family::findOrFail(13)->preferences->pluck('id')->all())->toBe([5, 4]);

    // --- Admin #12 (manages project 2) runs the lottery ---
    auth()->logout();
    $this->actingAs(User::findOrFail(12));
    setCurrentProject(2);

    // Make project 2's lottery (event #6) executable
    Event::findOrFail(6)->update(['start_date' => now()->subDay()]);

    // Project 2 has a unit/family mismatch on other types, so execution takes
    // the confirmation path (EXECUTE → mismatch warning → CONFIRM), as in the
    // lottery journey.
    visit(route('lottery'))
        ->waitForText('Execute Lottery')
        ->click('Execute Lottery')
        ->waitForText('Please write the following to confirm this action')
        ->fill('input[name="confirmation"]', 'EXECUTE')
        ->click('div[role="dialog"] button:has-text("Execute Lottery")')
        ->waitForText('Confirm and Execute')
        ->click('Confirm and Execute')
        ->waitForText('Please write the following to confirm this action')
        ->fill('input[name="confirmation"]', 'CONFIRM')
        ->click('div[role="dialog"] button:has-text("Confirm")')
        ->waitForText('Lottery Completed!')
        ->assertNoSmoke();

    // The assignment honors the ranked preferences: family 13's top pick was
    // Unit 5, family 14's was Unit 4 — each gets its first choice.
    expect(Family::findOrFail(13)->unit?->id)->toBe(5)
        ->and(Family::findOrFail(14)->unit?->id)->toBe(4)
        ->and(Event::withTrashed()->findOrFail(6)->audits()->exists())->toBeTrue();
});
