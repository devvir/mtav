<?php

namespace App\Services;

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Models\Post;
use App\Models\Project;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A project's forum: opening topics and replying to them. A new topic is told to the
 * project; a reply, to everyone who wrote in the topic before (never to its writer).
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
        $earlier = $topic->participants()->whereKeyNot($author->id)->pluck('id');

        $post = DB::transaction(function () use ($topic, $author, $body) {
            $post = $this->post($topic, $author, $body);
            $topic->forceFill(['replies_count' => $topic->replies_count + 1, 'last_post_at' => now()])->save();

            return $post;
        });

        foreach ($earlier as $participant) {
            $this->notifications->send(
                NotificationTarget::PRIVATE,
                NotificationType::FORUM_REPLY,
                'notifications.forum_reply_title',
                'notifications.forum_reply_message',
                params: ['name' => $author->name, 'title' => $topic->title],
                url: route('forum.show', [$topic->project_id, $topic, 'post' => $post->id]),
                trigger: $author,
                targetId: $participant,
            );
        }

        return $post;
    }

    /** A reply goes; the opening post goes only with its topic */
    public function remove(Post $post): void
    {
        DB::transaction(function () use ($post) {
            $topic = $post->topic;
            $post->delete();
            $topic->forceFill([
                'replies_count' => max(0, $topic->replies_count - 1),
                'last_post_at'  => $topic->posts()->max('created_at') ?? $topic->created_at,
            ])->save();
        });
    }

    private function post(Topic $topic, User $author, string $body): Post
    {
        $post = new Post(['body' => $body]);
        $post->forceFill(['topic_id' => $topic->id, 'project_id' => $topic->project_id, 'author_id' => $author->id])->save();

        return $post;
    }
}
