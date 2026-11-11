<?php

use App\Models\FamilyJoinRequest;
use App\Notifications\FamilyRequestNotification;
use App\Notifications\InvitationNotification;
use App\Notifications\JoinedNotification;

/**
 * What people wrote (the names of projects, families and people) reaches an email as they
 * typed it: never as a link, a picture or markup of its own.
 */

const A_FAKE_LINK = '[Reset your password](https://evil.example/reset)';

function expectNoFakeLink(string $html): void
{
    expect($html)->not->toContain('href="https://evil.example')
        ->not->toContain('src="https://evil.example')
        ->toContain('Reset your password');
}

it('shows a project\'s, a family\'s and an inviter\'s name in an invitation as text', function () {
    $project = aProject(['name' => A_FAKE_LINK]);
    $family = aFamily($project, attributes: ['name' => '![x](https://evil.example/pixel.png)']);
    $admin = anAdminOf($project);
    $admin->forceFill(['firstname' => A_FAKE_LINK])->save();
    $invitation = $project->invitations()->create([
        'family_id'  => $family->id, 'email' => 'ana@example.com', 'firstname' => '<b>Ana</b>',
        'token_hash' => 'x', 'invited_by' => $admin->id, 'expires_at' => now()->addWeek(),
    ]);

    $html = (string) (new InvitationNotification($invitation, 'the-token'))->toMail()->render();

    expectNoFakeLink($html);
    expect($html)->not->toContain('<b>Ana</b>')
        // The invitation's own button still leads where it should
        ->toContain('href="' . route('invitation.show', 'the-token') . '"');
});

it('shows a person\'s name as text in what a family is told, and in being added', function () {
    $project = aProject();
    $family = aFamily($project, attributes: ['name' => A_FAKE_LINK]);
    $member = aMemberOf($family);
    $asking = aUser(['firstname' => A_FAKE_LINK]);
    $project->memberships()->create(['user_id' => $asking->id]);
    $joinRequest = FamilyJoinRequest::create(['project_id' => $project->id, 'family_id' => $family->id, 'user_id' => $asking->id]);

    expectNoFakeLink((string) (new FamilyRequestNotification($joinRequest))->toMail($member)->render());
    expectNoFakeLink((string) (new JoinedNotification($member->membershipIn($project), $asking))->toMail($member)->render());
});
