<?php

use App\Models\Event;
use App\Models\Media;
use App\Models\Topic;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * What people came to rely on, removed, is kept for its admins to see and restore: who did
 * what stays traceable. A mistake taken back at once, that nothing else happened to, goes.
 */

beforeEach(function () {
    Storage::fake(Media::DISK);
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->member = aMemberOf(aFamily($this->project, aUnitType($this->project)));
});

function anEventOf($project, $by, array $attributes = []): Event
{
    return $project->events()->create([
        'creator_id' => $by->id, 'title' => 'Assembly', 'description' => 'All of us', 'type' => 'online',
        'is_published' => true, 'start_date' => now()->addWeek(), ...$attributes,
    ]);
}

function shareFile($test): Media
{
    $test->as($test->member, $test->project)->post(route('media.store'), [
        'description' => 'The minutes',
        'files'       => [UploadedFile::fake()->create('minutes.pdf', 10, 'application/pdf')],
    ]);

    return Media::latest('id')->first();
}

describe('An event', function () {
    it('goes for good if it never went out', function () {
        $event = anEventOf($this->project, $this->admin, ['is_published' => false]);
        $this->travel(2)->days();

        $this->as($this->admin, $this->project)->delete(route('manage.events.destroy', $event));

        expect(Event::withTrashed()->whereKey($event->id)->exists())->toBeFalse();
    });

    it('goes for good if removed at once', function () {
        $event = anEventOf($this->project, $this->admin);

        $this->as($this->admin, $this->project)->delete(route('manage.events.destroy', $event));

        expect(Event::withTrashed()->whereKey($event->id)->exists())->toBeFalse();
    });

    it('is kept once people answered, hidden, and restored whole', function () {
        $event = anEventOf($this->project, $this->admin, ['allows_rsvp' => true]);
        $this->as($this->member, $this->project)->put(route('events.rsvp', $event), ['attending' => true]);

        $this->as($this->admin, $this->project)->delete(route('manage.events.destroy', $event));

        expect(Event::whereKey($event->id)->exists())->toBeFalse()
            ->and(Event::onlyTrashed()->whereKey($event->id)->exists())->toBeTrue();
        expect($this->as($this->admin, $this->project)->get(route('events.index'))->inertiaProps('archive.count'))->toBe(1);

        $this->as($this->admin, $this->project)->put(route('manage.archive.restore', ['kind' => 'events', 'id' => $event->id]))
            ->assertSessionHasNoErrors();

        expect(Event::whereKey($event->id)->exists())->toBeTrue()
            ->and($event->fresh()->rsvps()->count())->toBe(1);
    });

    it('is kept once it has been out a while', function () {
        $event = anEventOf($this->project, $this->admin);
        $this->travel(2)->hours();

        $this->as($this->admin, $this->project)->delete(route('manage.events.destroy', $event));

        expect(Event::onlyTrashed()->whereKey($event->id)->exists())->toBeTrue();
    });
});

describe('A file', function () {
    it('taken back at once goes with its file', function () {
        $media = shareFile($this);
        $path = $media->path;

        $this->as($this->member, $this->project)->delete(route('media.destroy', $media));

        expect(Media::withTrashed()->whereKey($media->id)->exists())->toBeFalse();
        Storage::disk(Media::DISK)->assertMissing($path);
    });

    it('kept later keeps its file, and is restored whole', function () {
        $media = shareFile($this);
        $this->travel(2)->hours();

        $this->as($this->member, $this->project)->delete(route('media.destroy', $media));

        expect(Media::whereKey($media->id)->exists())->toBeFalse();
        Storage::disk(Media::DISK)->assertExists($media->path);

        $this->as($this->admin, $this->project)->put(route('manage.archive.restore', ['kind' => 'files', 'id' => $media->id]));

        expect(Media::whereKey($media->id)->exists())->toBeTrue();
    });
});

describe('A forum topic', function () {
    it('nobody answered, taken back at once, goes', function () {
        $this->as($this->member, $this->project)->post(route('forum.store'), ['title' => 'Oops there', 'body' => 'Nothing to say']);
        $topic = Topic::sole();

        $this->as($this->member, $this->project)->delete(route('forum.destroy', $topic));

        expect(Topic::withTrashed()->count())->toBe(0);
    });

    it('with answers is kept, and an admin restores it', function () {
        $this->as($this->member, $this->project)->post(route('forum.store'), ['title' => 'Kept one', 'body' => 'Something to say']);
        $topic = Topic::sole();
        $this->as($this->admin, $this->project)->post(route('forum.reply', $topic), ['body' => 'Agreed']);

        $this->as($this->admin, $this->project)->delete(route('forum.destroy', $topic));
        expect(Topic::count())->toBe(0);

        $this->as($this->admin, $this->project)->put(route('manage.archive.restore', ['kind' => 'forum', 'id' => $topic->id]));

        expect(Topic::sole()->title)->toBe('Kept one')
            ->and($topic->fresh()->posts()->count())->toBe(2);
    });
});

describe('The archive', function () {
    it('is for admins: everyone else sees no trace of it', function () {
        $event = anEventOf($this->project, $this->admin);
        $this->travel(2)->hours();
        $this->as($this->admin, $this->project)->delete(route('manage.events.destroy', $event));

        expect($this->as($this->member, $this->project)->get(route('events.index', ['archived' => 1]))->inertiaProps('archive'))->toBeNull();
        expect($this->as($this->member, $this->project)->put(route('manage.archive.restore', ['kind' => 'events', 'id' => $event->id])))->toBeDenied();
    });

    it('shows what was archived, on asking', function () {
        $event = anEventOf($this->project, $this->admin);
        $this->travel(2)->hours();
        $this->as($this->admin, $this->project)->delete(route('manage.events.destroy', $event));

        $hidden = $this->as($this->admin, $this->project)->get(route('events.index'))->inertiaProps('archive');
        expect($hidden['shown'])->toBeFalse()->and($hidden['items'])->toBeNull();

        $shown = $this->as($this->admin, $this->project)->get(route('events.index', ['archived' => 1]))->inertiaProps('archive');
        expect($shown['items'][0]['id'])->toBe($event->id);
    });

    it('never restores another project\'s', function () {
        $other = aProject();
        $event = anEventOf($other, anAdminOf($other));
        $this->travel(2)->hours();
        $event->delete();

        $this->as($this->admin, $this->project)->put(route('manage.archive.restore', ['kind' => 'events', 'id' => $event->id]))->assertNotFound();
        expect(Event::onlyTrashed()->whereKey($event->id)->exists())->toBeTrue();
    });

    it('takes only the kinds it knows', function () {
        $this->as($this->admin, $this->project)->put(route('manage.archive.restore', ['kind' => 'users', 'id' => 1]))->assertNotFound();
    });
});
