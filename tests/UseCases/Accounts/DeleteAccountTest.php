<?php

use App\Models\Media;
use App\Models\PushSubscription;
use App\Models\User;

/**
 * Someone deletes their own account, confirming with their email: they leave every
 * project; what they gave their projects stays there, without their name. Never the
 * deployment's owner, nor a project's only admin.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->member = aMemberOf(aFamily($this->project), ['email' => 'ana@example.com']);
});

function deleteAccount($test, User $user, string $confirmation)
{
    return $test->as($user)->delete(route('profile.destroy'), ['confirmation' => $confirmation]);
}

it('deletes an account confirmed with its email, signing its person out', function () {
    $this->member->pushSubscriptions()->create(['endpoint' => 'https://push.example.com/1', 'public_key' => 'k', 'auth_token' => 'a']);

    deleteAccount($this, $this->member, 'ana@example.com')->assertRedirect(route('home'));

    expect(User::find($this->member->id))->toBeNull()
        ->and($this->project->members()->count())->toBe(1)
        ->and(PushSubscription::count())->toBe(0);
    $this->assertGuest();
});

it('keeps what they shared in the project, without their name', function () {
    $media = Media::factory()->create(['project_id' => $this->project->id, 'owner_id' => $this->member->id]);

    deleteAccount($this, $this->member, 'ana@example.com');

    expect($media->fresh())->not->toBeNull()->and($media->fresh()->owner_id)->toBeNull();
});

it('asks for the email exactly', function () {
    deleteAccount($this, $this->member, 'someone@else.com')->assertSessionHasErrors('confirmation');

    expect(User::find($this->member->id))->not->toBeNull();
});

it('keeps a project\'s only admin, until another admin is named', function () {
    deleteAccount($this, $this->admin, $this->admin->email)->assertSessionHasErrors('confirmation');
    expect(User::find($this->admin->id))->not->toBeNull();

    $this->project->memberships()->where('user_id', $this->member->id)->update(['is_admin' => true]);
    deleteAccount($this, $this->admin, $this->admin->email)->assertSessionHasNoErrors();
    expect(User::find($this->admin->id))->toBeNull();
});

it('keeps the owner of the platform', function () {
    $owner = aUser(['email' => 'owner@example.com']);
    $owner->forceFill(['is_owner' => true, 'is_superadmin' => true])->save();

    deleteAccount($this, $owner, 'owner@example.com')->assertSessionHasErrors('confirmation');
    expect(User::find($owner->id))->not->toBeNull();
});
