<?php

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

/**
 * Opening a notification marks it read and leads to what it is about; one about nothing in
 * particular is marked read where it is.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->member = aMemberOf(aFamily($this->project));
});

function notify($test, ?string $url): Notification
{
    return app(NotificationService::class)->send(
        NotificationTarget::PROJECT,
        NotificationType::RESOURCE_CREATED,
        'New event: General assembly',
        'On Friday at 10, in the communal hall',
        url: $url,
        targetId: $test->project->id,
    );
}

describe('Joining a project', function () {
    beforeEach(function () {
        $this->older = aProject();
        $this->notice = fn (Project $project) => app(NotificationService::class)->send(
            NotificationTarget::PROJECT,
            NotificationType::RESOURCE_CREATED,
            'A title',
            'A message',
            targetId: $project->id,
        );
        $this->ownNotices = fn () => Notification::for($this->member->fresh())->where('target', NotificationTarget::PROJECT)->pluck('target_id')->all();
    });

    it('brings no earlier news of it, however long ago they signed up', function () {
        $this->travel(5)->days();
        ($this->notice)($this->older);
        $this->travel(1)->days();
        $this->older->memberships()->create(['user_id' => $this->member->id]);

        expect(($this->ownNotices)())->toBe([])
            ->and(Notification::for($this->member->fresh())->unreadBy($this->member)->where('target_id', $this->older->id)->count())->toBe(0);
    });

    it('brings what comes after, of that project and the ones they were in', function () {
        $this->travel(1)->days();
        $this->older->memberships()->create(['user_id' => $this->member->id]);
        $this->travel(1)->minutes();
        ($this->notice)($this->older);
        ($this->notice)($this->project);

        expect(($this->ownNotices)())->toEqualCanonicalizing([$this->older->id, $this->project->id]);
    });

    it('still brings what was told in the very moment they joined', function () {
        $this->freezeTime();
        $this->older->memberships()->create(['user_id' => $this->member->id]);
        ($this->notice)($this->older);

        expect(($this->ownNotices)())->toBe([$this->older->id]);
    });

    it('brings nothing to who is no longer in it', function () {
        ($this->notice)($this->project);
        $this->member->memberships()->delete();

        expect(($this->ownNotices)())->toBe([]);
    });
});

it('marks every unread notification read, however many, past what one statement can take', function () {
    $now = now();
    $rows = array_fill(0, 22_000, ['type' => NotificationType::RESOURCE_CREATED->value, 'target' => NotificationTarget::PROJECT->value, 'target_id' => $this->project->id, 'data' => '{}', 'created_at' => $now, 'updated_at' => $now]);
    collect($rows)->chunk(2000)->each(fn ($some) => DB::table('notifications')->insert($some->all()));

    $this->as($this->member, $this->project)->post(route('notifications.readAll'))->assertRedirect();

    expect(Notification::for($this->member->fresh())->unreadBy($this->member)->count())->toBe(0);
});

it('marks a notification read and leads to what it is about', function () {
    $notification = notify($this, route('events.index', $this->project));

    $this->as($this->member, $this->project)
        ->from(route('projects.show', $this->project))
        ->post(route('notifications.read', $notification))
        ->assertRedirect(route('events.index', $this->project, absolute: false));

    expect(Notification::for($this->member)->unreadBy($this->member)->count())->toBe(0);
});

it('marks a notification about nothing in particular read, staying on the page', function () {
    $notification = notify($this, null);

    $this->as($this->member, $this->project)
        ->from(route('projects.show', $this->project))
        ->post(route('notifications.read', $notification))
        ->assertRedirect(route('projects.show', $this->project));

    expect(Notification::for($this->member)->unreadBy($this->member)->count())->toBe(0);
});

it('keeps notifications of other projects out of reach', function () {
    $notification = notify($this, route('events.index', $this->project));

    $outsider = aMemberOf(aFamily(aProject()));

    expect($this->as($outsider)->post(route('notifications.read', $notification)))->toBeDenied();
    expect($notification->readers()->count())->toBe(0);
});

describe('What a project gains', function () {
    beforeEach(function () {
        Illuminate\Support\Facades\Storage::fake(App\Models\Media::DISK);
        $this->gaining = aProject();
        $this->gainingAdmin = anAdminOf($this->gaining);
        $this->neighbour = aMemberOf(aFamily($this->gaining, $this->gainingType = aUnitType($this->gaining)));
    });

    $seen = fn ($test) => collect($test->as($test->neighbour)->get(route('notifications.index'))->inertiaProps('notifications.data'));

    it('tells people of an event once it is out', function () use ($seen) {
        $store = fn (bool $published) => $this->as($this->gainingAdmin, $this->gaining)->post(route('manage.events.store'), [
            'title'      => 'Assembly', 'description' => 'All of us', 'type' => 'online', 'is_published' => $published,
            'start_date' => now()->addWeek()->toDateTimeString(), 'end_date' => now()->addWeek()->addHour()->toDateTimeString(),
        ])->assertSessionHasNoErrors();

        $store(false);
        expect($seen($this)->pluck('title'))->not->toContain(__('notifications.event_published_title'));

        $store(true);
        expect($seen($this)->pluck('title'))->toContain(__('notifications.event_published_title'));
    });

    it('never tells someone what they did themselves: only the others hear of it', function () use ($seen) {
        $this->as($this->gainingAdmin, $this->gaining)->post(route('manage.events.store'), [
            'title'      => 'Assembly', 'description' => 'All of us', 'type' => 'online', 'is_published' => true,
            'start_date' => now()->addWeek()->toDateTimeString(), 'end_date' => now()->addWeek()->addHour()->toDateTimeString(),
        ])->assertSessionHasNoErrors();
        $notice = Notification::where('type', NotificationType::EVENT_PUBLISHED)->sole();

        expect($seen($this)->pluck('id'))->toContain($notice->id)
            ->and(Notification::for($this->gainingAdmin)->pluck('id'))->not->toContain($notice->id)
            ->and(Notification::for($this->gainingAdmin)->unreadBy($this->gainingAdmin)->count())->toBe(0)
            ->and($notice->isFor($this->gainingAdmin))->toBeFalse();
    });

    it('tells the project someone joined, but not the one who joined', function () {
        $newcomer = aUser();
        $invitation = app(App\Services\InvitationService::class)->invite($this->gaining, $this->gainingAdmin, ['email' => $newcomer->email], null, asAdmin: false);
        app(App\Services\InvitationService::class)->accept($invitation, $newcomer);
        $joined = Notification::where('type', NotificationType::MEMBER_JOINED)->sole();

        expect(Notification::for($newcomer->fresh())->pluck('id'))->not->toContain($joined->id)
            ->and(Notification::for($this->neighbour)->pluck('id'))->toContain($joined->id);
    });

    it('tells people when a draft event goes out, once', function () use ($seen) {
        $event = $this->gaining->events()->create(['creator_id' => $this->gainingAdmin->id, 'title' => 'Draft', 'description' => 'Later', 'type' => 'online', 'is_published' => false, 'start_date' => now()->addWeek(), 'end_date' => now()->addWeek()->addHour()]);
        $update = fn () => $this->as($this->gainingAdmin, $this->gaining)->put(route('manage.events.update', $event), [
            'title'      => 'Draft', 'description' => 'Later', 'type' => 'online', 'is_published' => true,
            'start_date' => now()->addWeek()->toDateTimeString(), 'end_date' => now()->addWeek()->addHour()->toDateTimeString(),
        ])->assertSessionHasNoErrors();

        $update();
        $update();

        expect($seen($this)->where('title', __('notifications.event_published_title')))->toHaveCount(1);
    });

    it('tells people of files, in one notification for what comes together', function () use ($seen) {
        $share = fn () => $this->as($this->gainingAdmin, $this->gaining)->post(route('media.store'), [
            'description' => 'Minutes', 'files' => [Illuminate\Http\UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')],
        ])->assertSessionHasNoErrors();

        $share();
        $share();
        $share();

        $notices = $seen($this)->where('title', __('notifications.files_added_title'));
        expect($notices)->toHaveCount(1)
            ->and($notices->first()['message'])->toContain('3');
    });

    it('brings a bundled notification back as unread', function () {
        $add = fn (string $id) => $this->as($this->gainingAdmin, $this->gaining)->post(route('manage.units.store'), [
            'identifier' => $id, 'unit_type_id' => $this->gainingType->id,
        ])->assertSessionHasNoErrors();

        $add('A-1');
        $notice = App\Models\Notification::where('type', App\Enums\NotificationType::UNITS_ADDED)->sole();
        $notice->markAsReadBy($this->neighbour);

        $add('A-2');

        expect(App\Models\Notification::where('type', App\Enums\NotificationType::UNITS_ADDED)->sole()->data['params']['count'])->toBe('2')
            ->and($notice->readers()->count())->toBe(0);
    });

    it('starts a new notification after a while', function () {
        $add = fn (string $id) => $this->as($this->gainingAdmin, $this->gaining)->post(route('manage.units.store'), [
            'identifier' => $id, 'unit_type_id' => $this->gainingType->id,
        ]);

        $add('A-1');
        $this->travel(31)->minutes();
        $add('A-2');

        expect(App\Models\Notification::where('type', App\Enums\NotificationType::UNITS_ADDED)->count())->toBe(2);
    });

    it('tells people of families', function () use ($seen) {
        $this->as($this->gainingAdmin, $this->gaining)->post(route('manage.families.store'), [
            'name' => 'New one', 'unit_type_id' => $this->gainingType->id, 'email' => 'new@example.com',
        ])->assertSessionHasNoErrors();

        expect($seen($this)->pluck('title'))->toContain(__('notifications.families_added_title'));
    });
});
