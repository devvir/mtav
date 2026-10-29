<?php

/**
 * Arriving: guests find the page that presents the platform, signing in and up over it;
 * people signed in go straight on.
 */

it('shows guests the landing page', function () {
    $this->get(route('home'))->assertInertia(fn ($page) => $page->component('Welcome')->where('auth', null));
});

it('opens signing in, or signing up, over it', function () {
    $this->get(route('login'))->assertInertia(fn ($page) => $page->component('Welcome')->where('auth', 'login'));
    $this->get(route('register'))->assertInertia(fn ($page) => $page->component('Welcome')->where('auth', 'register'));
});

it('takes people signed in on to their projects', function () {
    $this->as(aUser())->get(route('home'))->assertRedirect(route('projects.index'));
});

it('asks people who have not confirmed their email to do so first', function () {
    $this->as(aUser(['email_verified_at' => null]))->get(route('home'))->assertRedirect(route('verification.notice'));
});
