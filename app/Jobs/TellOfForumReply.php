<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\ForumService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** A reply, told to those who wrote in its topic, however many: the writer is not kept waiting */
class TellOfForumReply implements ShouldQueue
{
    use Queueable;

    /** A second try would tell some of them twice */
    public int $tries = 1;

    public function __construct(public int $postId)
    {
        $this->onQueue('realtime');
    }

    public function handle(ForumService $forum): void
    {
        ($post = Post::find($this->postId)) && $forum->tellOfReply($post);
    }
}
