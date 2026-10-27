<?php

use App\Events\NotificationSent;
use App\Events\ProjectChanged;
use App\Services\ProjectService;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;

/**
 * Open pages follow what happens in a project without reloading: changes and
 * notifications are pushed to the project's channel, which only its people can join.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->member = aMemberOf(aFamily($this->project));
});

it('announces what changed in a project, and who changed it', function () {
    Event::fake([ProjectChanged::class]);

    $this->as($this->admin, $this->project)->post(route('manage.unit_types.store'), ['name' => 'Studio']);

    Event::assertDispatched(ProjectChanged::class, fn (ProjectChanged $event) => $event->projectId === $this->project->id
        && $event->resource === 'unit_types'
        && $event->action === 'created'
        && $event->by === $this->admin->id);
});

it('announces people joining under their page name', function () {
    Event::fake([ProjectChanged::class]);

    aMemberOf(aFamily($this->project));

    Event::assertDispatched(ProjectChanged::class, fn (ProjectChanged $event) => $event->resource === 'people');
});

it('pushes a project notification to the project channel', function () {
    Event::fake([NotificationSent::class]);
    $project = anUnapprovedProject();

    app(ProjectService::class)->approve($project, aSuperadmin());

    Event::assertDispatched(NotificationSent::class, fn (NotificationSent $event) => $event->broadcastOn()[0] instanceof PrivateChannel
        && $event->broadcastOn()[0]->name === "private-projects.{$project->id}");
});

it('lets only the project\'s people listen to it', function () {
    $outsider = aUser();
    $channel = Broadcast::driver()->getChannels()["projects.{id}"] ?? null;

    expect($channel)->not->toBeNull()
        ->and($channel($this->member, $this->project->id))->toBeTrue()
        ->and($channel($outsider, $this->project->id))->toBeFalse();
});

it('lets each person listen only to their own notifications', function () {
    $channel = Broadcast::driver()->getChannels()['users.{id}'];

    expect($channel($this->member, $this->member->id))->toBeTrue()
        ->and($channel($this->member, $this->admin->id))->toBeFalse();
});
