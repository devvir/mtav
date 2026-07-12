<?php

use App\Models\Event;
use App\Models\User;

uses()->group('Browser.Journeys');

/**
 * Journey 4 — Lottery.
 *
 * An admin reschedules the lottery, then executes it for real (GLPK solver):
 * the fixture's unit/family mismatch triggers the confirmation path, so the
 * journey covers both the warning and the typed double-confirmation, ending
 * with visible results. Finally a superadmin invalidates the execution,
 * unwinding every assignment.
 */
test('an admin reschedules and executes the lottery, a superadmin invalidates it', function () {
    config()->set('app.locale', 'en');

    // Admin #11 manages project 1; its lottery is Event #1 (scheduled +30d)
    $this->actingAs(User::find(11));
    setFirstProjectAsCurrent();

    // --- Reschedule the lottery ---
    visit(route('lottery'))
        ->waitForText('Lottery Settings')
        ->assertSee('The lottery will be available for execution after the scheduled date.')
        ->fill('input[name="start_date"]', '2027-03-15T18:00')
        ->fill('#description', 'Rescheduled by the lottery journey.')
        ->click('button[type="submit"]')
        ->waitForText('Great! The lottery details were updated.');

    expect(Event::find(1)->start_date->format('Y-m-d H:i'))->toBe('2027-03-15 18:00');

    // --- Make it executable (scheduled date reached) ---
    Event::find(1)->update(['start_date' => now()->subDay()]);

    // Execute: the fixture has more families than units per type, so the
    // first typed confirmation (EXECUTE) comes back with a mismatch warning
    // — flash state, so the whole flow stays on one page — and the second
    // typed confirmation (CONFIRM) acknowledges it and runs the lottery.
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

    // Execution soft-deletes the lottery event and assigns units to families
    expect(Event::withTrashed()->find(1)->trashed())->toBeTrue()
        ->and(
            App\Models\Unit::where('project_id', 1)->whereNotNull('family_id')->count()
        )->toBeGreaterThan(0);

    // --- A superadmin invalidates the execution (admins cannot) ---
    auth()->logout();
    $this->actingAs(User::find(1)); // superadmin1@example.com
    setCurrentProject(1);

    visit(route('lottery'))
        ->waitForText('Invalidate Lottery Execution')
        ->click('Invalidate Lottery')
        ->waitForText('Please write the following to confirm this action')
        ->fill('input[name="confirmation"]', 'INVALIDATE')
        ->click('div[role="dialog"] button:has-text("Invalidate Lottery")')
        ->waitForText('Lottery execution has been successfully invalidated. All unit assignments have been removed.')
        ->assertNoSmoke();

    // Invalidation restores the lottery event and clears every assignment
    expect(Event::find(1)->trashed())->toBeFalse()
        ->and(
            App\Models\Unit::where('project_id', 1)->whereNotNull('family_id')->count()
        )->toBe(0);
});
