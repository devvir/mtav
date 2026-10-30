<?php

use App\Models\Notification;
use App\Models\Post;
use App\Models\Topic;

/**
 * The forum: anyone in the project opens topics and replies; a new topic is told to the
 * project, a reply to whoever wrote in the topic before. Writers edit their posts; admins
 * pin, close and remove topics. No one outside the project reads it.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->ana = aMemberOf(aFamily($this->project), ['firstname' => 'Ana']);
    $this->beto = aMemberOf(aFamily($this->project), ['firstname' => 'Beto']);
});

function openTopic($test, $user, string $title = 'Assembly date', string $body = 'Which Saturday suits everyone?')
{
    return $test->as($user, $test->project)->post(route('forum.store', $test->project), compact('title', 'body'));
}

function replyTo($test, $user, Topic $topic, string $body = 'The 14th works for us')
{
    return $test->as($user, $test->project)->post(route('forum.reply', [$test->project, $topic]), ['body' => $body]);
}

it('lets anyone in the project open a topic, telling the project', function () {
    openTopic($this, $this->ana)->assertSessionHasNoErrors();

    $topic = Topic::sole();
    expect($topic)->title->toBe('Assembly date')->author_id->toBe($this->ana->id)
        ->and($topic->posts()->sole()->body)->toBe('Which Saturday suits everyone?')
        ->and(Notification::where('type', 'forum_topic')->sole()->target_id)->toBe($this->project->id);
});

it('tells whoever wrote in a topic of each reply, but not the one replying', function () {
    openTopic($this, $this->ana);
    $topic = Topic::sole();

    replyTo($this, $this->beto, $topic);
    replyTo($this, $this->admin, $topic, 'Fine by me');

    $told = Notification::where('type', 'forum_reply')->pluck('target_id')->sort()->values()->all();
    expect($told)->toBe(collect([$this->ana->id, $this->ana->id, $this->beto->id])->sort()->values()->all())
        ->and($topic->fresh()->replies_count)->toBe(2);
});

it('lists pinned topics first, then the liveliest', function () {
    openTopic($this, $this->ana, 'Old');
    openTopic($this, $this->ana, 'Pinned');
    openTopic($this, $this->ana, 'Lively');
    Topic::firstWhere('title', 'Pinned')->update(['is_pinned' => true]);
    Topic::firstWhere('title', 'Old')->forceFill(['last_post_at' => now()->subDay()])->save();

    $titles = collect($this->as($this->beto, $this->project)->get(route('forum.index', $this->project))->inertiaProps('topics.data'))->pluck('title');

    expect($titles->all())->toBe(['Pinned', 'Lively', 'Old']);
});

it('takes no replies in a closed topic, but from its admins', function () {
    openTopic($this, $this->ana);
    $topic = Topic::sole();
    $this->as($this->admin, $this->project)->put(route('forum.update', [$this->project, $topic]), ['is_closed' => true]);

    expect(replyTo($this, $this->beto, $topic))->toBeDenied();
    replyTo($this, $this->admin, $topic)->assertSessionHasNoErrors();
});

it('lets writers edit their posts, and no one else', function () {
    openTopic($this, $this->ana);
    $post = Post::sole();

    expect($this->as($this->beto, $this->project)->put(route('forum.posts.update', [$this->project, $post]), ['body' => 'Hacked']))->toBeDenied();
    $this->as($this->ana, $this->project)->put(route('forum.posts.update', [$this->project, $post]), ['body' => 'Which Sunday?']);

    expect($post->fresh())->body->toBe('Which Sunday?')->edited_at->not->toBeNull();
});

it('lets members pin nothing, and admins remove a topic with its posts', function () {
    openTopic($this, $this->ana);
    $topic = Topic::sole();
    replyTo($this, $this->beto, $topic);

    expect($this->as($this->beto, $this->project)->put(route('forum.update', [$this->project, $topic]), ['is_pinned' => true]))->toBeDenied()
        ->and($this->as($this->ana, $this->project)->delete(route('forum.destroy', [$this->project, $topic])))->toBeDenied();

    $this->as($this->admin, $this->project)->delete(route('forum.destroy', [$this->project, $topic]));
    expect(Topic::count())->toBe(0);
});

it('keeps the forum from people outside the project', function () {
    openTopic($this, $this->ana);
    $outsider = aMemberOf(aFamily(aProject()));

    $this->as($outsider)->get(route('forum.show', [$this->project, Topic::sole()]))->assertNotFound();
});
