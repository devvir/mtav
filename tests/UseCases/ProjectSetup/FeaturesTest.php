<?php

use App\Models\Media;
use App\Models\Notification as AppNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Forum, chat, gallery, files and events: on or off per project. Off, a feature is gone from
 * its pages and its address, and what it holds stays for when it comes back.
 */

beforeEach(function () {
    Storage::fake(Media::DISK);
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->member = aMemberOf(aFamily($this->project, aUnitType($this->project)));
});

function turnOff($project, string ...$features): void
{
    $project->update(['feature_choices' => array_fill_keys($features, false)]);
}

it('has everything on until an admin says otherwise', function () {
    expect($this->as($this->member, $this->project)->get(route('projects.show'))->inertiaProps('project.features'))
        ->toBe(['forum' => true, 'chat' => true, 'assistant' => true, 'gallery' => true, 'files' => true, 'events' => true]);
});

it('lets admins turn features off, from the project\'s settings', function () {
    $this->as($this->admin, $this->project)->put(route('manage.settings.update'), [
        'name' => $this->project->name, 'description' => $this->project->description, 'feature_choices' => ['forum' => false, 'chat' => false],
    ])->assertSessionHasNoErrors();

    expect($this->project->fresh()->hasFeature('forum'))->toBeFalse()
        ->and($this->project->fresh()->hasFeature('events'))->toBeTrue();
});

it('takes only features it knows', function () {
    $this->as($this->admin, $this->project)->put(route('manage.settings.update'), [
        'name' => $this->project->name, 'description' => $this->project->description, 'feature_choices' => ['lottery' => false],
    ])->assertSessionHasErrors('feature_choices');
});

it('asks for it when a project starts', function () {
    $this->as(aUser())->post(route('projects.store'), [
        'name' => 'Ours', 'description' => 'Our project', 'allows_guests' => true, 'feature_choices' => ['forum' => false],
    ])->assertSessionHasNoErrors();

    $project = App\Models\Project::firstWhere('name', 'Ours');
    expect($project->allows_guests)->toBeTrue()->and($project->hasFeature('forum'))->toBeFalse();
});

it('removes a feature\'s address while off, and keeps what it holds for when it comes back', function (string $feature, string $route) {
    $this->as($this->member, $this->project)->get(route($route))->assertOk();

    turnOff($this->project, $feature);
    $this->as($this->member, $this->project)->get(route($route))->assertNotFound();

    turnOff($this->project);
    $this->as($this->member, $this->project)->get(route($route))->assertOk();
})->with([
    'forum'  => ['forum', 'forum.index'],
    'chat'   => ['chat', 'chat.index'],
    'events' => ['events', 'events.index'],
]);

it('keeps the lottery with events off', function () {
    turnOff($this->project, 'events');

    $this->as($this->member, $this->project)->get(route('lottery.show'))->assertOk();
});

it('keeps the forum\'s topics while it is off', function () {
    $this->as($this->member, $this->project)->post(route('forum.store'), ['title' => 'Kept here', 'body' => 'And back again']);
    turnOff($this->project, 'forum');
    turnOff($this->project);

    $this->as($this->member, $this->project)->get(route('forum.index'))->assertSee('Kept here');
});

it('has its media page while either gallery or files is on, showing what is', function () {
    turnOff($this->project, 'gallery');
    expect($this->as($this->member, $this->project)->get(route('media.index'))->inertiaProps('category'))->toBe('files');

    turnOff($this->project, 'files');
    expect($this->as($this->member, $this->project)->get(route('media.index', ['category' => 'files']))->inertiaProps('category'))->toBe('media');

    turnOff($this->project, 'gallery', 'files');
    $this->as($this->member, $this->project)->get(route('media.index'))->assertNotFound();
});

it('takes in no file of a kind that is off', function () {
    turnOff($this->project, 'gallery');

    $this->as($this->member, $this->project)->post(route('media.store'), [
        'description' => 'A picture',
        'files'       => [UploadedFile::fake()->image('photo.jpg')],
    ])->assertSessionHasErrors('files.0');
});

it('offers neither chat nor uploads while off', function () {
    turnOff($this->project, 'chat', 'gallery', 'files');
    $can = $this->as($this->member, $this->project)->get(route('projects.show'))->inertiaProps('project.can');

    expect($can['chat'])->toBeFalse()->and($can['upload'])->toBeFalse();
});

it('leaves a home without what is off, asking for nothing of it', function () {
    turnOff($this->project, 'gallery', 'events');
    $page = $this->as($this->member, $this->project)->get(route('projects.show'));

    expect($page->inertiaProps('media'))->toBeNull()->and($page->inertiaProps('events'))->toBeNull();
    expect(AppNotification::count())->toBeGreaterThanOrEqual(0);
});

describe('What the variant says', function () {
    function theSpec(array $features): void
    {
        config(['variants.mtav.features' => $features]);
    }

    it('makes a feature off and not configurable not exist: no address, no setting, no way in', function () {
        theSpec(['chat' => ['default' => true, 'configurable' => false], 'forum' => ['default' => false, 'configurable' => false]]);

        $this->as($this->member, $this->project)->get(route('forum.index'))->assertNotFound();
        expect($this->project->fresh()->hasFeature('forum'))->toBeFalse()
            ->and(App\Support\Variant::choices())->toBe([]);
    });

    it('keeps a feature on and not configurable, whatever a project says', function () {
        theSpec(['chat' => ['default' => true, 'configurable' => false]]);
        $this->project->update(['feature_choices' => ['chat' => false]]);

        expect($this->project->fresh()->hasFeature('chat'))->toBeTrue();
    });

    it('starts a configurable feature as its default, until the project chooses', function () {
        theSpec(['events' => ['default' => false, 'configurable' => true], 'gallery' => ['default' => true, 'configurable' => true]]);

        expect($this->project->hasFeature('events'))->toBeFalse()
            ->and($this->project->hasFeature('gallery'))->toBeTrue();

        $this->project->update(['feature_choices' => ['events' => true, 'gallery' => false]]);

        expect($this->project->fresh()->hasFeature('events'))->toBeTrue()
            ->and($this->project->fresh()->hasFeature('gallery'))->toBeFalse();
    });

    it('offers admins only what may be chosen, and refuses the rest', function () {
        theSpec(['events' => ['default' => false, 'configurable' => true], 'chat' => ['default' => true, 'configurable' => false]]);

        $page = $this->as($this->admin, $this->project)->get(route('manage.settings.edit'));

        expect((array) $page->inertiaProps('featureChoices'))->toBe(['events' => false]);

        $this->put(route('manage.settings.update'), [
            'name' => $this->project->name, 'description' => $this->project->description, 'feature_choices' => ['chat' => false],
        ])->assertSessionHasErrors('feature_choices');
    });

    it('has no assistant without a chat', function () {
        $this->project->update(['feature_choices' => ['chat' => false]]);

        expect($this->project->fresh()->hasFeature('assistant'))->toBeFalse();
    });

    it('leaves the assistant out of the chat when it is off', function () {
        $this->project->update(['feature_choices' => ['assistant' => false]]);

        expect(App\Models\Conversation::of($this->member, $this->project->fresh())->where('is_assistant', true)->exists())->toBeFalse();
    });
});
