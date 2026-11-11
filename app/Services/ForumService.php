<?php

namespace App\Services;

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Jobs\TellOfForumReply;
use App\Models\Post;
use App\Models\Project;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A project's forum: opening topics and replying to them. A new topic is told to the
 * project; a reply, to everyone still in the project who wrote in the topic before (never to its writer).
 */
class ForumService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function open(Project $project, User $author, string $title, string $body): Topic
    {
        $topic = DB::transaction(function () use ($project, $author, $title, $body) {
            $topic = new Topic(['title' => $title]);
            $topic->forceFill(['project_id' => $project->id, 'author_id' => $author->id, 'last_post_at' => now()])->save();
            $this->post($topic, $author, $body);

            return $topic;
        });

        $this->notifications->send(
            NotificationTarget::PROJECT,
            NotificationType::FORUM_TOPIC,
            'notifications.forum_topic_title',
            'notifications.forum_topic_message',
            params: ['name' => $author->name, 'title' => $title],
            url: route('forum.show', [$project, $topic]),
            trigger: $author,
            targetId: $project->id,
        );

        return $topic;
    }

    public function reply(Topic $topic, User $author, string $body): Post
    {
        $post = DB::transaction(function () use ($topic, $author, $body) {
            $held = $this->hold($topic);
            $post = $this->post($held, $author, $body);
            $held->forceFill(['replies_count' => $this->replies($held), 'last_post_at' => now()])->save();

            return $post;
        });

        rescue(fn () => TellOfForumReply::dispatch($post->id));

        return $post;
    }

    public function tellOfReply(Post $post): void
    {
        $topic = $post->topic;
        $author = $post->author;

        if (! $topic) {
            return;
        }

        // Whoever has left the project hears no more of it
        $earlier = $topic->participants()->whereKeyNot($post->author_id)
            ->where(fn ($people) => $people
                ->where('is_superadmin', true)
                ->orWhereHas('memberships', fn ($q) => $q->where('project_id', $topic->project_id)))
            ->pluck('id');

        foreach ($earlier as $participant) {
            $this->notifications->send(
                NotificationTarget::PRIVATE,
                NotificationType::FORUM_REPLY,
                'notifications.forum_reply_title',
                'notifications.forum_reply_message',
                params: ['name' => $author?->name, 'title' => $topic->title],
                url: route('forum.show', [$topic->project_id, $topic, 'post' => $post->id]),
                trigger: $author,
                targetId: $participant,
            );
        }
    }

    /** A reply goes; the opening post goes only with its topic */
    public function remove(Post $post): void
    {
        DB::transaction(function () use ($post) {
            $topic = $this->hold($post->topic);
            $post->retire();
            $topic->forceFill([
                'replies_count' => $this->replies($topic),
                'last_post_at'  => $topic->posts()->max('created_at') ?? $topic->created_at,
            ])->save();
        });
    }

    /**
     * Admins remove a topic; its writer too, while nobody has replied. Decided with the topic
     * held still: a reply arriving at the same moment keeps it.
     */
    public function removeTopic(Topic $topic, User $by): void
    {
        DB::transaction(function () use ($topic, $by) {
            $topic = $this->hold($topic);

            abort_unless($by->can('remove', $topic), 403);

            $topic->retire();
        });
    }

    /** The topic as it is now, held until the transaction ends: replies and removals take turns */
    private function hold(Topic $topic): Topic
    {
        return Topic::whereKey($topic->id)->lockForUpdate()->firstOrFail();
    }

    /** Counted from the posts, never from a number read earlier: replies at the same moment all count */
    private function replies(Topic $topic): int
    {
        return max(0, $topic->posts()->count() - 1);
    }

    private function post(Topic $topic, User $author, string $body): Post
    {
        $post = new Post(['body' => $body]);
        $post->forceFill(['topic_id' => $topic->id, 'project_id' => $topic->project_id, 'author_id' => $author->id])->save();

        return $post;
    }
}
