<?php

/**
 * HTTP-level tests for the Lottery routes not covered by the execution tests:
 * the index page, Member preferences submission, Admin lottery updates and
 * Superadmin invalidation authorization.
 */

use App\Models\Event;
use App\Models\User;
use App\Services\LotteryService;

uses()->group('Feature.Lottery.Routes');

describe('Lottery page', function () {
    it('loads for an Admin with a Project selected', function () {
        setFirstProjectAsCurrent();

        $response = $this->visitRoute('lottery', asAdmin: 11, redirects: false);

        expect($response)->toBeOk()->toUsePage('Lottery');
    });

    it('loads for a Member (with their preferences)', function () {
        setFirstProjectAsCurrent();

        $response = $this->visitRoute('lottery', asMember: 102, redirects: false);

        expect($response)->toBeOk()->toUsePage('Lottery');
    });

    it('redirects guests to the login page', function () {
        $response = $this->visitRoute('lottery', redirects: false);

        expect($response)->toRedirectTo('login');
    });
});

describe('Updating Lottery details', function () {
    it('lets an Admin update the lottery description and date', function () {
        setFirstProjectAsCurrent();
        $lottery = Event::withoutGlobalScopes()->find(1); // Project #1 lottery
        $newDate = now()->addDays(60);

        $response = $this->sendPatchRequest(['lottery.update', $lottery->id], asAdmin: 11, redirects: false, data: [
            'description' => 'Rescheduled lottery',
            'start_date'  => $newDate->toDateTimeString(),
        ]);

        $response->assertSessionHas('success');
        expect($lottery->fresh())
            ->description->toBe('Rescheduled lottery')
            ->start_date->format('Y-m-d')->toBe($newDate->format('Y-m-d'));
    });

    it('denies Members from updating the lottery', function () {
        setFirstProjectAsCurrent();
        $lottery = Event::withoutGlobalScopes()->find(1);

        $response = $this->sendPatchRequest(['lottery.update', $lottery->id], asMember: 102, redirects: false, data: [
            'description' => 'Hacked description',
        ]);

        $response->assertRedirect(route('dashboard')); // 403 renders as Dashboard redirect
        expect($lottery->fresh()->description)->not->toBe('Hacked description');
    });

    it('refuses updates to an unpublished (locked) lottery', function () {
        setFirstProjectAsCurrent();
        $lottery = Event::withoutGlobalScopes()->find(1);
        $lottery->update(['is_published' => false]);

        $response = $this->sendPatchRequest(['lottery.update', $lottery->id], asAdmin: 11, redirects: false, data: [
            'description' => 'Should not apply',
        ]);

        $response->assertSessionHas('error');
        expect($lottery->fresh()->description)->not->toBe('Should not apply');
    });
});

describe('Saving Member preferences', function () {
    it('persists the submitted preference order for the Member\'s Family', function () {
        setFirstProjectAsCurrent();

        // Member #105 belongs to Family #5 (Unit Type #2: units #1, #2 in Project #1)
        $member = User::find(105);
        $family = $member->asMember()->family;
        $unitIds = $family->unitType->units()->pluck('id');

        $preferences = $unitIds->reverse()->values()
            ->map(fn ($id, $index) => ['id' => $id, 'order' => $index + 1])
            ->all();

        $response = $this->submitFormToRoute('lottery.preferences', asMember: $member->id, redirects: false, data: [
            'preferences' => $preferences,
        ]);

        $response->assertSessionHasNoErrors();

        $saved = app(LotteryService::class)->preferences($family->fresh());
        expect(collect($saved)->pluck('id')->take($unitIds->count())->all())
            ->toBe($unitIds->reverse()->values()->all());
    });

    it('rejects preferences for Units outside the Family\'s Unit Type', function () {
        setFirstProjectAsCurrent();

        $response = $this->submitFormToRoute('lottery.preferences', asMember: 105, redirects: false, data: [
            'preferences' => [['id' => 999, 'order' => 1]],
        ]);

        $response->assertSessionHasErrors();
    });

    it('denies Admins from submitting preferences', function () {
        setFirstProjectAsCurrent();

        $response = $this->submitFormToRoute('lottery.preferences', asAdmin: 11, redirects: false, data: [
            'preferences' => [],
        ]);

        $response->assertRedirect(route('dashboard')); // 403 renders as Dashboard redirect
    });
});

describe('Invalidating a Lottery execution', function () {
    it('denies non-superadmin Admins', function () {
        setFirstProjectAsCurrent();
        $lottery = Event::withoutGlobalScopes()->find(1);

        $this->actingAs(User::find(11));
        $response = $this->delete(route('lottery.invalidate', $lottery->id));

        $response->assertRedirect(route('dashboard')); // 403 renders as Dashboard redirect
    });

    it('allows a Superadmin to invalidate an executed lottery', function () {
        setFirstProjectAsCurrent();
        $lottery = Event::withoutGlobalScopes()->find(1);

        // Simulate a previous execution: audit trail + an assigned unit
        \App\Models\LotteryAudit::create([
            'execution_uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'project_id'     => $lottery->project_id,
            'lottery_id'     => $lottery->id,
            'type'           => 'project_execution',
            'audit'          => [],
        ]);
        \App\Models\Unit::withoutGlobalScopes()->whereId(1)->update(['family_id' => 4]);

        $this->actingAs(User::find(1));
        $response = $this->delete(route('lottery.invalidate', $lottery->id));

        $response->assertSessionHas('success');

        // Unit assignments are cleared and an INVALIDATE audit is recorded
        expect(\App\Models\Unit::withoutGlobalScopes()->find(1)->family_id)->toBeNull()
            ->and($lottery->fresh()->audits()->where('type', 'invalidate')->exists())->toBeTrue();
    });
});
