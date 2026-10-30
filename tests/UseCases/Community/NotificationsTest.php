<?php

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Models\Notification;
use App\Services\NotificationService;

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
            'title' => 'Assembly', 'description' => 'All of us', 'type' => 'online', 'is_published' => $published,
            'start_date' => now()->addWeek()->toDateTimeString(), 'end_date' => now()->addWeek()->addHour()->toDateTimeString(),
        ])->assertSessionHasNoErrors();

        $store(false);
        expect($seen($this)->pluck('title'))->not->toContain(__('notifications.event_published_title'));

        $store(true);
        expect($seen($this)->pluck('title'))->toContain(__('notifications.event_published_title'));
    });

    it('tells people when a draft event goes out, once', function () use ($seen) {
        $event = $this->gaining->events()->create(['creator_id' => $this->gainingAdmin->id, 'title' => 'Draft', 'description' => 'Later', 'type' => 'online', 'is_published' => false, 'start_date' => now()->addWeek(), 'end_date' => now()->addWeek()->addHour()]);
        $update = fn () => $this->as($this->gainingAdmin, $this->gaining)->put(route('manage.events.update', $event), [
            'title' => 'Draft', 'description' => 'Later', 'type' => 'online', 'is_published' => true,
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
            'name' => 'New one', 'unit_type_id' => $this->gainingType->id,
        ])->assertSessionHasNoErrors();

        expect($seen($this)->pluck('title'))->toContain(__('notifications.families_added_title'));
    });
});
