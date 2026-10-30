<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Response;

/**
 * A project's chat: someone's conversations (the latest first), one of them open, and
 * starting new ones with anyone of the project.
 */
class ChatController extends Controller
{
    /** Messages a conversation opens with; earlier ones come on asking */
    private const PAGE = 50;

    public function __construct(private ChatService $chat)
    {
    }

    public function index(Request $request, Project $project): Response
    {
        $this->authorize('startChat', [Conversation::class, $project]);

        return $this->page($request, $project);
    }

    public function show(Request $request, Project $project, Conversation $conversation): Response
    {
        $this->authorize('readChat', $conversation);
        $this->chat->read($conversation, $request->user());

        return $this->page($request, $project, $conversation);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('startChat', [Conversation::class, $project]);
        $data = $request->validate([
            'people'   => ['required', 'array', 'min:1', 'max:100'],
            'people.*' => ['integer'],
            'title'    => ['nullable', 'string', 'max:80'],
        ]);

        $conversation = $this->chat->start($project, $request->user(), $data['people'], $data['title'] ?? null);

        return to_route('chat.show', [$project, $conversation]);
    }

    public function send(Request $request, Project $project, Conversation $conversation): RedirectResponse
    {
        $this->authorize('writeChat', $conversation);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $this->chat->send($conversation, $request->user(), trim($data['body']));

        return to_route('chat.show', [$project, $conversation]);
    }

    /** A group's name, and people added to it */
    public function update(Request $request, Project $project, Conversation $conversation): RedirectResponse
    {
        $this->authorize('manageGroup', $conversation);
        $data = $request->validate([
            'title'    => ['nullable', 'string', 'max:80'],
            'people'   => ['array', 'max:100'],
            'people.*' => ['integer'],
        ]);

        $this->chat->update($conversation, $data['title'] ?? null, $data['people'] ?? []);

        return back();
    }

    public function leave(Request $request, Project $project, Conversation $conversation): RedirectResponse
    {
        $this->authorize('leaveGroup', $conversation);
        $this->chat->leave($conversation, $request->user());

        return to_route('chat.index', $project)->with('success', __('flash.conversation_left'));
    }

    public function destroyMessage(Request $request, Project $project, Message $message): RedirectResponse
    {
        $this->authorize('unsendMessage', [Conversation::class, $message]);
        $this->chat->remove($message);

        return back();
    }

    /** The conversations, the one open (if any) with its latest messages, and who may join */
    private function page(Request $request, Project $project, ?Conversation $open = null): Response
    {
        $user = $request->user();
        $conversations = Conversation::of($user, $project)
            ->with(['participants:id,firstname,lastname,avatar', 'lastMessage.author:id,firstname'])
            ->orderByDesc('last_message_at')->orderByDesc('id')
            ->get();
        $unread = Conversation::of($user, $project)->unreadBy($user)->pluck('id')->flip();

        return inertia('Member/Chat/Index', [
            'conversations' => $conversations->map(fn (Conversation $conversation) => [
                ...$this->summary($conversation, $user),
                'unread' => $unread->has($conversation->id),
            ])->values(),
            'conversation' => $open ? [
                ...$this->summary($open->load('participants:id,firstname,lastname,avatar'), $user),
                'people' => $open->participants->map(fn (User $person) => $this->person($person))->values(),
                'can'    => [
                    'update' => $user->can('manageGroup', $open),
                    'leave'  => $user->can('leaveGroup', $open),
                ],
            ] : null,
            'messages' => fn () => $open ? $this->messages($request, $open) : null,
            // Everyone of the project one may talk with, for a new conversation (or a group's new people)
            'people' => fn () => $project->members()->whereKeyNot($user->id)
                ->orderBy('firstname')->orderBy('lastname')->get(['users.id', 'firstname', 'lastname', 'avatar'])
                ->map(fn (User $person) => $this->person($person))->values(),
        ]);
    }

    /** The latest messages, as many as shown (?count=…, more on asking), oldest first */
    private function messages(Request $request, Conversation $conversation): array
    {
        $count = min(max($request->integer('count', self::PAGE), self::PAGE), 1000);
        $page = $conversation->messages()->with('author:id,firstname,lastname,avatar')
            ->latest('id')->limit($count + 1)->get();

        return [
            'data' => $page->take($count)->reverse()->map(fn (Message $message) => [
                'id'         => $message->id,
                'body'       => $message->body,
                'author'     => $message->author ? $this->person($message->author) : null,
                'mine'       => $message->user_id === $request->user()->id,
                'created_at' => $message->created_at,
            ])->values(),
            'earlier' => $page->count() > $count,
        ];
    }

    private function summary(Conversation $conversation, User $user): array
    {
        $others = $conversation->participants->reject(fn (User $person) => $person->id === $user->id)->values();
        $last = $conversation->lastMessage;

        return [
            'id' => $conversation->id,
            // A group by its name, else by its people; one on one, by the other person
            'title' => $conversation->is_community ? __('chat.community') : ($conversation->title ?: match ($others->count()) {
                0       => __('chat.only_you'),
                1       => $others[0]->name,
                default => $others->pluck('firstname')->join(', '),
            }),
            'is_group'     => $conversation->is_group,
            'is_community' => $conversation->is_community,
            'faces'        => $others->take(3)->map(fn (User $person) => $this->person($person))->values(),
            'last'         => $last ? [
                'body'   => Str::limit($last->body, 90),
                'author' => $last->user_id === $user->id ? null : $last->author?->firstname,
                'mine'   => $last->user_id === $user->id,
                'at'     => $last->created_at,
            ] : null,
        ];
    }

    private function person(User $user): array
    {
        return [
            'id'     => $user->id,
            'name'   => $user->name,
            'avatar' => $user->avatar ? Storage::disk('public')->url($user->avatar) : null,
        ];
    }
}
