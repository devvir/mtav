<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/**
 * People: invitations in every situation, roles, leaving.
 */

beforeEach(function () {
    Notification::fake();

    $this->project = aProject(['name' => 'Cooperativa Las Acacias']);
    $this->admin = anAdminOf($this->project);
    $this->family = aFamily($this->project, null, ['name' => 'García']);
});

it('invites a fellow admin, without a family', function () {
    $this->as($this->admin);

    visit(route('manage.invitations.index', $this->project))
        ->fill('email', 'pablo@example.com')
        ->check('is_admin')
        ->select('family_id', '')
        ->click('Send the invitation')
        ->assertSee('Invitation sent to pablo@example.com.')
        ->assertSee('pablo@example.com');

    expect(Invitation::sole())->is_admin->toBeTrue()->family_id->toBeNull();
});

it('withdraws an invitation, whose link then explains it is no longer valid', function () {
    $this->as($this->admin);

    visit(route('manage.invitations.index', $this->project))
        ->fill('email', 'ana@example.com')
        ->select('family_id', (string) $this->family->id)
        ->click('Send the invitation')
        ->click('[aria-label="Withdraw"]')
        ->click(inDialog('Withdraw'))
        ->assertSee('The invitation was withdrawn.');

    $link = invitationLinkFor('ana@example.com');
    auth()->logout();

    visit($link)
        ->assertSee('This invitation is no longer valid')
        ->click('Go to sign in')
        ->assertPathIs('/login');
});

it('lets someone with an account join after signing in', function () {
    $ana = aUser(['email' => 'ana@example.com', 'password' => 'Ana-pass-1234']);
    $this->as($this->admin);

    visit(route('manage.invitations.index', $this->project))
        ->fill('email', 'ana@example.com')
        ->select('family_id', (string) $this->family->id)
        ->click('Send the invitation');

    $link = invitationLinkFor('ana@example.com');
    auth()->logout();
    visit(route('home'))->assertSee('Sign in');

    visit($link)
        ->assertSee('This invitation is for ana@example.com. Sign in with that account to accept it.')
        ->fill('email', 'ana@example.com')
        ->fill('password', 'Ana-pass-1234')
        ->click('[type="submit"]')
        ->assertSee('You are invited')
        ->click('Join Cooperativa Las Acacias')
        ->assertSee('Welcome to Cooperativa Las Acacias!');

    expect($ana->fresh()->familyIn($this->project)?->id)->toBe($this->family->id);
});

it('tells someone signed in as another person that the invitation is not theirs', function () {
    $this->as($this->admin);

    visit(route('manage.invitations.index', $this->project))
        ->fill('email', 'ana@example.com')
        ->select('family_id', (string) $this->family->id)
        ->click('Send the invitation');

    $this->as(aUser(['email' => 'someone.else@example.com']));

    visit(invitationLinkFor('ana@example.com'))
        ->assertSee('This invitation is for ana@example.com, but you are signed in as someone.else@example.com.')
        ->assertSee('Sign out to accept it');
});

it('accepts or declines invitations from the projects page', function () {
    $ana = aUser(['email' => 'ana@example.com']);
    $other = aProject(['name' => 'Cooperativa Los Ceibos']);
    app(\App\Services\InvitationService::class)->invite($this->project, $this->admin, ['email' => 'ana@example.com'], $this->family, false);
    app(\App\Services\InvitationService::class)->invite($other, anAdminOf($other), ['email' => 'ana@example.com'], aFamily($other), false);
    $this->as($ana);

    visit(route('projects.index'))
        ->assertSee('Invitations waiting for you')
        ->click('Decline')
        ->assertSee('You declined the invitation.')
        ->click('Join')
        ->assertSee('Welcome to');

    expect($ana->fresh()->memberships()->count())->toBe(1);
});

it('lets a member invite the rest of their family', function () {
    $member = aMemberOf($this->family);
    $this->as($member);

    visit(route('projects.show', $this->project))
        ->click('Invite a family member')
        ->assertSee('Invite the people of the García family')
        ->fill('email', 'cousin@example.com')
        ->click('Send the invitation')
        ->assertSee('Invitation sent to cousin@example.com.')
        ->assertSee('Waiting for an answer');

    expect(Invitation::sole()->family_id)->toBe($this->family->id);
});

it('changes someone\'s family and role, keeping at least one admin', function () {
    $member = aMemberOf($this->family, ['firstname' => 'Ana', 'lastname' => 'García']);
    $other = aFamily($this->project, null, ['name' => 'Pérez']);
    $this->as($this->admin);

    visit(route('manage.people.index', $this->project))
        ->click('[aria-label="Change role or family of Ana García"]')
        ->select('family_id', (string) $other->id)
        ->check('is_admin')
        ->click(inDialog('Save'))
        ->assertSee('Saved.');

    expect($member->fresh()->membershipIn($this->project))
        ->family_id->toBe($other->id)
        ->is_admin->toBeTrue();

    $this->as($member->fresh());
    $member->membershipIn($this->project)->update(['is_admin' => true]);
    $this->admin->membershipIn($this->project)->delete();

    visit(route('manage.people.index', $this->project))
        ->click('[aria-label="Change role or family of Ana García"]')
        ->uncheck('is_admin')
        ->click(inDialog('Save'))
        ->assertSee('A project needs at least one admin.');
});

it('removes someone from the project', function () {
    $member = aMemberOf($this->family, ['firstname' => 'Ana', 'lastname' => 'García']);
    $this->as($this->admin);

    visit(route('manage.people.index', $this->project))
        ->click('[aria-label="Remove Ana García"]')
        ->click(inDialog('Remove'))
        ->assertSee('The person was removed from the project.');

    expect($member->fresh()->canAccess($this->project))->toBeFalse();
});

it('lets a member leave the project', function () {
    $member = aMemberOf($this->family);
    $this->as($member);

    visit(route('people.show', [$this->project, $member]))
        ->click('Leave the project')
        ->click(inDialog('Leave'))
        ->assertSee('You left Cooperativa Las Acacias.');

    expect(User::find($member->id)->canAccess($this->project))->toBeFalse();
});

it('leads admins from the people to the invitations', function () {
    $this->as($this->admin);

    visit(route('manage.people.index', $this->project))
        ->click('Invite people')
        ->assertPathIs(route('manage.invitations.index', $this->project, absolute: false));
});

it('leads someone from their own person page to their profile', function () {
    $member = aMemberOf($this->family);
    $this->as($member);

    visit(route('people.show', [$this->project, $member]))
        ->click('Edit your profile')
        ->assertPathIs(route('profile.edit', absolute: false));
});
