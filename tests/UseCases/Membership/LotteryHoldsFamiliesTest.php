<?php

use App\Models\Family;
use App\Models\FamilyJoinRequest;
use App\Models\Invitation;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Support\Facades\Notification as Notifications;
use Illuminate\Validation\ValidationException;

/**
 * While a run is being solved, what it decides from holds still: families are neither
 * registered, started, brought back nor joined, each refused with the reason.
 */

beforeEach(function () {
    Notifications::fake();
    $this->project = aProject(['allows_guests' => true]);
    $this->type = aUnitType($this->project);
    $this->family = aFamily($this->project, $this->type, ['name' => 'Pereira']);
    $this->member = aMemberOf($this->family);
    $this->admin = anAdminOf($this->project);
    $this->guest = User::factory()->create();
    $this->project->memberships()->create(['user_id' => $this->guest->id]);
});

it('registers no family while a run is being solved, inviting nobody', function () {
    aLotteryInProgress($this->project);

    $this->as($this->admin, $this->project)->post(route('manage.families.store'), [
        'name' => 'Silva', 'unit_type_id' => $this->type->id, 'email' => 'silva@example.com',
    ])->assertSessionHasErrors('lottery');

    expect($this->project->families()->where('name', 'Silva')->exists())->toBeFalse()
        ->and(Invitation::count())->toBe(0);
});

it('starts nobody\'s family once a run began, even one that passed the check before', function () {
    aLotteryInProgress($this->project);

    expect(fn () => app(FamilyService::class)->start($this->guest, $this->project, ['name' => 'Silva', 'unit_type_id' => $this->type->id]))
        ->toThrow(ValidationException::class);

    expect($this->project->families()->where('name', 'Silva')->exists())->toBeFalse()
        ->and($this->guest->fresh()->familyIn($this->project))->toBeNull();
});

it('lets nobody into a family on asking while a run is being solved', function () {
    $joinRequest = FamilyJoinRequest::create(['project_id' => $this->project->id, 'family_id' => $this->family->id, 'user_id' => $this->guest->id]);
    aLotteryInProgress($this->project);

    $this->as($this->member, $this->project)->put(route('family.requests.accept', $joinRequest))->assertSessionHasErrors('lottery');

    expect($this->guest->fresh()->familyIn($this->project))->toBeNull()
        ->and(FamilyJoinRequest::find($joinRequest->id))->not->toBeNull();
});

it('brings no archived family back by an invitation while a run is being solved', function () {
    $archived = aFamily($this->project, $this->type, ['name' => 'Silva']);
    $archived->delete();
    aLotteryInProgress($this->project);

    $this->as($this->admin, $this->project)->post(route('manage.invitations.store'), [
        'email' => 'silva@example.com', 'family_id' => $archived->id,
    ])->assertSessionHasErrors('lottery');

    expect(Family::onlyTrashed()->find($archived->id))->not->toBeNull()
        ->and(Invitation::count())->toBe(0);
});
