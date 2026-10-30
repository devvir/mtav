<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Project;
use App\Models\Topic;
use App\Models\User;
use App\Services\ForumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
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
            ->when($request->q, fn ($q, $search) => $q->whereLike('title', "%{$search}%"))
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_post_at')
            ->paginate(30)
            ->withQueryString();

        return inertia('Member/Forum/Index', [
            'topics' => JsonResource::collection($topics->through(fn (Topic $topic) => [
                ...$this->topic($topic),
                'excerpt' => Str::limit(strip_tags((string) $topic->opening?->body), 160),
            ])),
            'filters' => ['q' => $request->q],
        ]);
    }

    public function show(Request $request, Project $project, Topic $topic): Response
    {
        $user = $request->user();
        $posts = $topic->posts()->with('author:id,firstname,lastname,avatar')->paginate(30)->withQueryString();

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
                'can'        => ['update' => $user->can('update', $post), 'delete' => $user->can('delete', $post)],
            ])),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [Topic::class, $project]);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body'  => ['required', 'string', 'max:20000'],
        ]);

        $topic = $this->forum->open($project, $request->user(), $data['title'], $data['body']);

        return to_route('forum.show', [$project, $topic])->with('success', __('flash.topic_opened'));
    }

    public function reply(Request $request, Project $project, Topic $topic): RedirectResponse
    {
        $this->authorize('reply', $topic);
        $data = $request->validate(['body' => ['required', 'string', 'max:20000']]);

        $post = $this->forum->reply($topic, $request->user(), $data['body']);

        return to_route('forum.show', [$project, $topic, 'page' => $topic->posts()->paginate(30)->lastPage(), 'post' => $post->id]);
    }

    /** Admins pin or close a topic; its title, its writer or admins */
    public function update(Request $request, Project $project, Topic $topic): RedirectResponse
    {
        $manages = $request->user()->can('update', $topic);
        $data = $request->validate([
            'title'     => ['sometimes', 'required', 'string', 'max:160'],
            'is_pinned' => ['sometimes', 'boolean'],
            'is_closed' => ['sometimes', 'boolean'],
        ]);
        $mine = $topic->author_id === $request->user()->id;
        abort_unless($manages || ($mine && array_keys($data) === ['title']), 403);

        $topic->update($data);

        return back();
    }

    /** Admins remove a topic; its writer too, while nobody has replied */
    public function destroy(Request $request, Project $project, Topic $topic): RedirectResponse
    {
        abort_unless(
            $request->user()->can('delete', $topic) || ($topic->author_id === $request->user()->id && ! $topic->replies_count),
            403,
        );
        $topic->delete();

        return to_route('forum.index', $project)->with('success', __('flash.topic_removed'));
    }

    public function updatePost(Request $request, Project $project, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);
        $post->forceFill([...$request->validate(['body' => ['required', 'string', 'max:20000']]), 'edited_at' => now()])->save();

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
        return $user ? [
            'id'     => $user->id,
            'name'   => $user->name,
            'avatar' => $user->avatar ? Storage::disk('public')->url($user->avatar) : null,
        ] : null;
    }
}
