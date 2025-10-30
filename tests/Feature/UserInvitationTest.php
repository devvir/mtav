<?php

// Copilot - pending review

namespace Tests\Feature;

use App\Events\UserRegistration;
use App\Mail\AdminInvitationMail;
use App\Mail\MemberInvitationMail;
use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\InvitationTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_create_member_which_triggers_invitation_event()
    {
        Event::fake();
        $admin = Admin::factory()->create();
        $project = Project::factory()->create();
        $admin->projects()->attach($project);
        $family = Family::factory()->create(['project_id' => $project->id]);

        $this->actingAs($admin)->post(route('members.store'), [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'family' => $family->id,
            'project_id' => $project->id,
        ]);

        Event::assertDispatched(UserRegistration::class, function ($event) {
            return $event->user instanceof Member
                && $event->user->email === 'john@example.com'
                && !empty($event->token);
        });
    }

    /** @test */
    public function admin_can_create_admin_which_triggers_invitation_event()
    {
        Event::fake();
        $superadmin = Admin::factory()->superadmin()->create();
        $project = Project::factory()->create();

        $this->actingAs($superadmin)->post(route('admins.store'), [
            'firstname' => 'Jane',
            'lastname' => 'Admin',
            'email' => 'jane@example.com',
            'project_ids' => [$project->id],
        ]);

        Event::assertDispatched(UserRegistration::class, function ($event) {
            return $event->user instanceof Admin
                && $event->user->email === 'jane@example.com'
                && !empty($event->token);
        });
    }

    /** @test */
    public function created_member_has_hashed_invitation_token_as_password()
    {
        $admin = Admin::factory()->create();
        $project = Project::factory()->create();
        $admin->projects()->attach($project);
        $family = Family::factory()->create(['project_id' => $project->id]);

        $this->actingAs($admin)->post(route('members.store'), [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'family' => $family->id,
            'project_id' => $project->id,
        ]);

        $member = User::where('email', 'john@example.com')->first();

        $this->assertNotNull($member->password);
        $this->assertNull($member->email_verified_at);
        $this->assertTrue(str_starts_with($member->password, '$2y$')); // bcrypt hash
    }

    /** @test */
    public function created_admin_has_hashed_invitation_token_as_password()
    {
        $superadmin = Admin::factory()->superadmin()->create();
        $project = Project::factory()->create();

        $this->actingAs($superadmin)->post(route('admins.store'), [
            'firstname' => 'Jane',
            'lastname' => 'Admin',
            'email' => 'jane@example.com',
            'project_ids' => [$project->id],
        ]);

        $admin = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($admin->password);
        $this->assertNull($admin->email_verified_at);
        $this->assertTrue(str_starts_with($admin->password, '$2y$')); // bcrypt hash
    }

    /** @test */
    public function created_admin_is_attached_to_specified_projects()
    {
        $superadmin = Admin::factory()->superadmin()->create();
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        $this->actingAs($superadmin)->post(route('admins.store'), [
            'firstname' => 'Jane',
            'lastname' => 'Admin',
            'email' => 'jane@example.com',
            'project_ids' => [$project1->id, $project2->id],
        ]);

        $admin = Admin::where('email', 'jane@example.com')->first();

        $this->assertCount(2, $admin->projects);
        $this->assertTrue($admin->projects->contains($project1));
        $this->assertTrue($admin->projects->contains($project2));
    }

    /** @test */
    public function user_registration_event_sends_member_invitation_email()
    {
        Mail::fake();
        $member = Member::factory()->create();
        $family = Family::factory()->create();
        $member->family()->associate($family);
        $member->save();
        $token = 'test-token-123';

        event(new UserRegistration($member, $token));

        Mail::assertSent(MemberInvitationMail::class, function ($mail) use ($member, $token) {
            return $mail->member->id === $member->id
                && $mail->token === $token
                && $mail->hasTo($member->email);
        });
    }

    /** @test */
    public function user_registration_event_sends_admin_invitation_email()
    {
        Mail::fake();
        $admin = Admin::factory()->create();
        $token = 'test-token-123';

        event(new UserRegistration($admin, $token));

        Mail::assertSent(AdminInvitationMail::class, function ($mail) use ($admin, $token) {
            return $mail->admin->id === $admin->id
                && $mail->token === $token
                && $mail->hasTo($admin->email);
        });
    }

    /** @test */
    public function member_invitation_email_contains_confirmation_url_with_email_and_token()
    {
        $member = Member::factory()->create(['email' => 'test@example.com']);
        $token = 'test-token-xyz';

        $mail = new MemberInvitationMail($member, $token);
        $content = $mail->content();

        $this->assertEquals('emails.member-invitation', $content->view);
        $this->assertEquals($member, $content->with['member']);
        $this->assertStringContainsString('email=test@example.com', $content->with['confirmationUrl']);
        $this->assertStringContainsString('token=test-token-xyz', $content->with['confirmationUrl']);
    }

    /** @test */
    public function admin_invitation_email_contains_confirmation_url_with_email_and_token()
    {
        $admin = Admin::factory()->create(['email' => 'admin@example.com']);
        $token = 'admin-token-xyz';

        $mail = new AdminInvitationMail($admin, $token);
        $content = $mail->content();

        $this->assertEquals('emails.admin-invitation', $content->view);
        $this->assertEquals($admin, $content->with['admin']);
        $this->assertStringContainsString('email=admin@example.com', $content->with['confirmationUrl']);
        $this->assertStringContainsString('token=admin-token-xyz', $content->with['confirmationUrl']);
    }

    /** @test */
    public function invitation_confirmation_page_shows_form_with_valid_token()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $invitation = InvitationTokenService::generate();
        $user->update(['password' => $invitation['hashed']]);

        $response = $this->get(route('invitation.confirm', [
            'email' => 'test@example.com',
            'token' => $invitation['token'],
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/CompleteRegistration')
            ->has('user')
            ->where('email', 'test@example.com')
            ->where('token', $invitation['token'])
        );
    }

    /** @test */
    public function invitation_confirmation_redirects_with_error_if_email_missing()
    {
        $response = $this->get(route('invitation.confirm', [
            'token' => 'some-token',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', __('Invalid invitation link.'));
    }

    /** @test */
    public function invitation_confirmation_redirects_with_error_if_token_missing()
    {
        $response = $this->get(route('invitation.confirm', [
            'email' => 'test@example.com',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', __('Invalid invitation link.'));
    }

    /** @test */
    public function invitation_confirmation_redirects_with_error_if_user_not_found()
    {
        $response = $this->get(route('invitation.confirm', [
            'email' => 'nonexistent@example.com',
            'token' => 'some-token',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', __('User not found.'));
    }

    /** @test */
    public function invitation_confirmation_redirects_with_error_if_token_invalid()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $invitation = InvitationTokenService::generate();
        $user->update(['password' => $invitation['hashed']]);

        $response = $this->get(route('invitation.confirm', [
            'email' => 'test@example.com',
            'token' => 'wrong-token',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', __('Invalid or expired invitation token.'));
    }

    /** @test */
    public function invitation_confirmation_redirects_if_user_already_verified()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);
        $invitation = InvitationTokenService::generate();
        $user->update(['password' => $invitation['hashed']]);

        $response = $this->get(route('invitation.confirm', [
            'email' => 'test@example.com',
            'token' => $invitation['token'],
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('info', __('Your account is already active. Please log in.'));
    }

    /** @test */
    public function user_can_complete_registration_with_valid_token_and_password()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $invitation = InvitationTokenService::generate();
        $user->update(['password' => $invitation['hashed']]);

        $response = $this->post(route('invitation.complete'), [
            'email' => 'test@example.com',
            'token' => $invitation['token'],
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success', __('Registration completed! You can now log in.'));

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('NewSecurePassword123', $user->password));
    }

    /** @test */
    public function registration_completion_fails_with_invalid_token()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $invitation = InvitationTokenService::generate();
        $user->update(['password' => $invitation['hashed']]);

        $response = $this->post(route('invitation.complete'), [
            'email' => 'test@example.com',
            'token' => 'wrong-token',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', __('Invalid or expired invitation token.'));

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    /** @test */
    public function registration_completion_validates_password_required()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $invitation = InvitationTokenService::generate();
        $user->update(['password' => $invitation['hashed']]);

        $response = $this->post(route('invitation.complete'), [
            'email' => 'test@example.com',
            'token' => $invitation['token'],
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function registration_completion_validates_password_confirmation_matches()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $invitation = InvitationTokenService::generate();
        $user->update(['password' => $invitation['hashed']]);

        $response = $this->post(route('invitation.complete'), [
            'email' => 'test@example.com',
            'token' => $invitation['token'],
            'password' => 'SecurePassword123',
            'password_confirmation' => 'DifferentPassword123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function registration_completion_can_update_optional_fields()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'firstname' => 'Old',
            'lastname' => 'Name',
        ]);
        $invitation = InvitationTokenService::generate();
        $user->update(['password' => $invitation['hashed']]);

        $response = $this->post(route('invitation.complete'), [
            'email' => 'test@example.com',
            'token' => $invitation['token'],
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
            'firstname' => 'New',
            'lastname' => 'Name',
            'phone' => '+1234567890',
            'legal_id' => 'ABC123',
        ]);

        $response->assertRedirect(route('login'));

        $user->refresh();
        $this->assertEquals('New', $user->firstname);
        $this->assertEquals('Name', $user->lastname);
        $this->assertEquals('+1234567890', $user->phone);
        $this->assertEquals('ABC123', $user->legal_id);
    }

    /** @test */
    public function invitation_token_service_generates_unique_tokens()
    {
        $token1 = InvitationTokenService::generate();
        $token2 = InvitationTokenService::generate();

        $this->assertNotEquals($token1['token'], $token2['token']);
        $this->assertNotEquals($token1['hashed'], $token2['hashed']);
    }

    /** @test */
    public function invitation_token_service_generates_base64_encoded_tokens()
    {
        $invitation = InvitationTokenService::generate();

        // Base64 encoded string should only contain valid base64 characters
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $invitation['token']);

        // Should be 44 characters long (32 bytes base64 encoded)
        $this->assertEquals(44, strlen($invitation['token']));
    }

    /** @test */
    public function invitation_token_service_can_verify_valid_token()
    {
        $invitation = InvitationTokenService::generate();

        $this->assertTrue(
            InvitationTokenService::verify($invitation['token'], $invitation['hashed'])
        );
    }

    /** @test */
    public function invitation_token_service_rejects_invalid_token()
    {
        $invitation = InvitationTokenService::generate();

        $this->assertFalse(
            InvitationTokenService::verify('wrong-token', $invitation['hashed'])
        );
    }

    /** @test */
    public function member_invitation_loads_family_and_project_relationships()
    {
        $admin = Admin::factory()->create();
        $project = Project::factory()->create();
        $admin->projects()->attach($project);
        $family = Family::factory()->create(['project_id' => $project->id]);

        $this->actingAs($admin)->post(route('members.store'), [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'family' => $family->id,
            'project_id' => $project->id,
        ]);

        $member = Member::where('email', 'john@example.com')->first();

        $this->assertEquals($family->id, $member->family_id);
        $this->assertTrue($member->projects->contains($project));
    }

    /** @test */
    public function completed_registration_allows_user_to_login_with_new_password()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $invitation = InvitationTokenService::generate();
        $user->update(['password' => $invitation['hashed']]);

        // Complete registration
        $this->post(route('invitation.complete'), [
            'email' => 'test@example.com',
            'token' => $invitation['token'],
            'password' => 'MyNewPassword123',
            'password_confirmation' => 'MyNewPassword123',
        ]);

        // Attempt login
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'MyNewPassword123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }
}
