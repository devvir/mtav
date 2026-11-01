<?php

use App\Models\Media;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Someone deletes their own account, confirming with their password: they leave every
 * project; what they gave their projects stays there, without their name. Never the
 * deployment's owner, nor a project's only admin.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->member = aMemberOf(aFamily($this->project), ['email' => 'ana@example.com']);
});

function deleteAccount($test, User $user, ?string $password = null)
{
    return $test->as($user)->delete(route('profile.destroy'), ['password' => $password ?? config('app.demo_password') ?? 'password']);
}

it('deletes an account confirmed with its password, signing its person out', function () {
    $this->member->pushSubscriptions()->create(['endpoint' => 'https://push.example.com/1', 'public_key' => 'k', 'auth_token' => 'a']);

    deleteAccount($this, $this->member)->assertRedirect(route('home'));

    expect(User::find($this->member->id))->toBeNull()
        ->and($this->project->members()->count())->toBe(1)
        ->and(PushSubscription::count())->toBe(0);
    $this->assertGuest();
});

it('keeps what they shared in the project, without their name', function () {
    $media = Media::factory()->create(['project_id' => $this->project->id, 'owner_id' => $this->member->id]);

    deleteAccount($this, $this->member);

    expect($media->fresh())->not->toBeNull()->and($media->fresh()->owner_id)->toBeNull();
});

it('asks for the password, and the email shown on the page is not enough', function () {
    deleteAccount($this, $this->member, 'not-it')->assertSessionHasErrors('password');
    $this->as($this->member)->delete(route('profile.destroy'), ['confirmation' => 'ana@example.com'])->assertSessionHasErrors('password');

    expect(User::find($this->member->id))->not->toBeNull();
    $this->assertAuthenticatedAs($this->member);
});

it('takes only a few tries a minute', function () {
    foreach (range(1, 10) as $_) {
        deleteAccount($this, $this->member, 'not-it');
    }

    session()->flush();
    deleteAccount($this, $this->member)->assertSessionHas('error', __('flash.too_many_attempts'));

    expect(User::find($this->member->id))->not->toBeNull();
});

it('keeps someone signed in when refused as a project\'s only admin', function () {
    deleteAccount($this, $this->admin)->assertSessionHasErrors('confirmation');

    $this->assertAuthenticatedAs($this->admin);
});

it('holds the projects still while it checks: two admins going at once cannot both pass', function () {
    $second = aMemberOf(aFamily($this->project));
    $this->project->memberships()->where('user_id', $second->id)->update(['is_admin' => true]);

    $checks = [];
    DB::listen(function ($query) use (&$checks) {
        if (str_contains($query->sql, 'for update') && str_contains($query->sql, 'projects')) {
            $checks[] = DB::transactionLevel();
        }
    });
    $outside = DB::transactionLevel();

    deleteAccount($this, $this->admin)->assertSessionHasNoErrors();

    expect($checks)->not->toBeEmpty()->and($checks[0])->toBeGreaterThan($outside);
});

it('keeps a project\'s only admin, until another admin is named', function () {
    deleteAccount($this, $this->admin)->assertSessionHasErrors('confirmation');
    expect(User::find($this->admin->id))->not->toBeNull();

    $this->project->memberships()->where('user_id', $this->member->id)->update(['is_admin' => true]);
    deleteAccount($this, $this->admin)->assertSessionHasNoErrors();
    expect(User::find($this->admin->id))->toBeNull();
});

it('keeps the owner of the platform', function () {
    $owner = aUser(['email' => 'owner@example.com']);
    $owner->forceFill(['is_owner' => true, 'is_superadmin' => true])->save();

    deleteAccount($this, $owner)->assertSessionHasErrors('confirmation');
    expect(User::find($owner->id))->not->toBeNull();
});
