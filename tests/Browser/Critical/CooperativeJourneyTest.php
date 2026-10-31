<?php

uses()->group('critical');

use App\Models\Family;
use App\Models\Project;
use App\Models\User;
use App\Notifications\InvitationNotification;
use App\Services\Lottery\PreferencesService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/**
 * The life of a cooperative, end to end, through the real UI: someone signs up and
 * starts a project, sets it up, invites a member; the member joins from the emailed
 * link and ranks the units; the admin runs the lottery (GLPK); the member sees which
 * unit the family received.
 */
it('takes a cooperative from sign-up to its lottery', function () {
    Notification::fake();

    // Sign up and start a project
    visit(route('register'))
        ->fill('firstname', 'Laura')
        ->fill('lastname', 'Admin')
        ->fill('email', 'laura@example.com')
        ->fill('password', 'a-Strong-pass-123')
        ->fill('password_confirmation', 'a-Strong-pass-123')
        ->click('Create the account')
        ->assertSee('Confirm your email');

    // The confirmation link from the email
    $laura = User::firstWhere('email', 'laura@example.com');
    $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $laura->id, 'hash' => sha1($laura->email)]);

    visit($link)->assertSee('Your email address is confirmed. Welcome!');

    visit(route('projects.create'))
        ->fill('name', 'Cooperativa Las Acacias')
        ->fill('description', 'Two dwellings, two families')
        ->click('Start the project')
        ->assertPresent('.note-mini')
        ->assertNoSmoke();

    $project = Project::firstWhere('name', 'Cooperativa Las Acacias');

    // Unit types, units and families
    visit(route('units.index', $project))
        ->click('Add the first unit type')
        ->fill('name', '2 bedrooms')
        ->click(inDialog('Add the unit type'))
        ->assertSee('The unit type was added.')
        ->click('Add the first unit')
        ->fill('identifier', 'A-101')
        ->click('Add and continue with another')
        ->assertSee('The unit was added.')
        ->assertValue('[name="identifier"]', '')
        ->fill('identifier', 'A-102')
        ->click(inDialog('Add the unit'))
        ->assertSeeIn('.ds-panel', 'A-102');

    foreach (['García', 'Pérez'] as $name) {
        visit(route('families.index', [$project, 'new' => 1]))
            ->fill('name', $name)
            ->click(inDialog('Add the family'))
            ->assertSee('The family was added.');
    }

    // Invite a member into the García family
    $garcia = Family::firstWhere('name', 'García');

    visit(route('manage.invitations.index', $project))
        ->fill('email', 'ana@example.com')
        ->fill('firstname', 'Ana')
        ->select('family_id', (string) $garcia->id)
        ->click('Send the invitation')
        ->assertSee('Invitation sent to ana@example.com.');

    $token = null;
    Notification::assertSentOnDemand(InvitationNotification::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    // The member joins from the link
    visit(route('home'))->click('[aria-label="Your account"]')->click('Sign out')->assertSee('Sign in');

    visit(route('invitation.show', $token))
        ->assertSee('You are invited')
        ->fill('password', 'another-Strong-pass-1')
        ->fill('password_confirmation', 'another-Strong-pass-1')
        ->click('Join Cooperativa Las Acacias')
        ->assertSee('Welcome to Cooperativa Las Acacias!')
        ->assertNoSmoke();

    // …and moves the unit their family ranks second to the top
    $second = app(PreferencesService::class)->ranking($garcia)[1];

    visit(route('lottery.show', $project))
        ->assertSee('Your preferences')
        ->click("[aria-label=\"Move {$second->identifier} up\"]")
        ->wait(2);

    expect($garcia->preferences()->first()?->id)->toBe($second->id);

    // The other family wants the other unit most (default rankings differ per family, and two
    // families wanting the same unit would leave the outcome to chance)
    $perez = Family::firstWhere('name', 'Pérez');
    $others = app(PreferencesService::class)->ranking($perez)->reject(fn ($unit) => $unit->is($second));
    app(PreferencesService::class)->rank($perez, [...$others->pluck('id'), $second->id]);

    // The admin runs the lottery
    $project->lottery->update(['start_date' => now()->subMinute()]);
    $this->as(User::firstWhere('email', 'laura@example.com'));

    visit(route('lottery.show', $project))
        ->click('Run the lottery')
        ->fill('[role="dialog"] input', 'RUN')
        ->keys('[role="dialog"] input', 'Enter')
        ->assertSee('The lottery is done: every family has its unit.');

    expect($garcia->fresh()->unit?->id)->toBe($second->id);

    // The member sees the outcome
    $this->as(User::firstWhere('email', 'ana@example.com'));

    visit(route('lottery.show', $project))
        ->assertSee('The lottery is done')
        ->assertSee($second->identifier)
        ->assertNoSmoke();
});
