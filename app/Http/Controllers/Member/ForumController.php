<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use App\Http\Requests\TopicChangeRequest;
use App\Http\Requests\TopicRequest;
use App\Http\Resources\PersonResource;
use App\Models\Post;
use App\Models\Project;
use App\Models\Topic;
use App\Models\User;
use App\Services\ArchiveService;
use App\Services\ForumService;
use App\Support\Like;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Inertia\Response;

/**
 * A project's forum: its topics (pinned first, then the liveliest), each a conversation
 */
class ForumController extends Controller
{
    public function __construct(private ForumService $forum)
    {
    }

    public function index(Request $request, Project $project): Response
    {
        $topics = $project->topics()
            ->with('author:id,firstname,lastname,avatar', 'opening:id,topic_id,body')
            ->when($request->q, fn ($q, $search) => $q->whereLike('title', Like::containing($search)))
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_post_at')
            ->paginate(config('lists.per_page.topics'))
            ->withQueryString();

        return inertia('Member/Forum/Index', [
            'topics' => JsonResource::collection($topics->through(fn (Topic $topic) => [
                ...$this->topic($topic),
                'excerpt' => Str::limit(strip_tags((string) $topic->opening?->body), 160),
            ])),
            'filters' => ['q' => $request->q],
            'archive' => app(ArchiveService::class)->forPage($request, $project, 'forum'),
        ]);
    }

    public function show(Request $request, Project $project, Topic $topic): Response
    {
        $user = $request->user();
        $posts = $topic->posts()->with('author:id,firstname,lastname,avatar')->paginate(self::postsPerPage())->withQueryString();
        $opening = $topic->opening;

        return inertia('Member/Forum/Show', [
            'topic' => [
                ...$this->topic($topic->load('author:id,firstname,lastname,avatar')),
                'can' => [
                    'reply'  => $user->can('reply', $topic),
                    'manage' => $user->can('update', $topic),
                ],
            ],
            'posts' => JsonResource::collection($posts->through(fn (Post $post) => [
                'id'         => $post->id,
                'body'       => $post->body,
                'author'     => $this->person($post->author),
                'created_at' => $post->created_at,
                'edited_at'  => $post->edited_at,
                'can'        => [
                    'update' => $user->can('update', $post),
                    // The opening post goes with its topic
                    'delete' => $post->is($opening) ? $user->can('remove', $topic) : $user->can('delete', $post),
                ],
            ])),
        ]);
    }

    public function store(TopicRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();
        $topic = $this->forum->open($project, $request->user(), $data['title'], $data['body']);

        return to_route('forum.show', [$project, $topic])->with('success', __('flash.topic_opened'));
    }

    public function reply(PostRequest $request, Project $project, Topic $topic): RedirectResponse
    {
        $post = $this->forum->reply($topic, $request->user(), $request->validated('body'));
        $lastPage = max(1, (int) ceil($topic->posts()->count() / self::postsPerPage()));

        return to_route('forum.show', [$project, $topic, 'page' => $lastPage, 'post' => $post->id]);
    }

    /** Admins pin or close a topic; its title, its writer or admins */
    public function update(TopicChangeRequest $request, Project $project, Topic $topic): RedirectResponse
    {
        $this->authorize($request->renamesOnly() ? 'rename' : 'update', $topic);

        $topic->update($request->validated());

        return back();
    }

    /** Admins remove a topic; its writer too, while nobody has replied */
    public function destroy(Request $request, Project $project, Topic $topic): RedirectResponse
    {
        $this->forum->removeTopic($topic, $request->user());

        return to_route('forum.index', $project)->with('success', __('flash.topic_removed'));
    }

    public function updatePost(PostRequest $request, Project $project, Post $post): RedirectResponse
    {
        $post->forceFill([...$request->validated(), 'edited_at' => now()])->save();

        return back();
    }

    /** A reply goes alone; the opening post, with its topic */
    public function destroyPost(Request $request, Project $project, Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);
        $topic = $post->topic;
        if ($topic->opening->is($post)) {
            return $this->destroy($request, $project, $topic);
        }
        $this->forum->remove($post);

        return back();
    }

    private static function postsPerPage(): int
    {
        return config('lists.per_page.posts');
    }

    private function topic(Topic $topic): array
    {
        return [
            'id'            => $topic->id,
            'title'         => $topic->title,
            'is_pinned'     => $topic->is_pinned,
            'is_closed'     => $topic->is_closed,
            'replies_count' => $topic->replies_count,
            'last_post_at'  => $topic->last_post_at,
            'created_at'    => $topic->created_at,
            'author'        => $this->person($topic->author),
        ];
    }

    private function person(?User $user): ?array
    {
        return $user ? (new PersonResource($user))->resolve() : null;
    }
}
