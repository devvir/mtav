<?php

namespace App\Http\Controllers\Member;

use App\Enums\ProjectFeature;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use App\Models\Report;
use App\Models\User;
use App\Notifications\NewReportNotice;
use App\Services\Assistant\Places;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Response;

/**
 * A project's chat: someone's conversations (the latest first), one of them open, and
 * starting new ones with anyone of the project. With no project (AccountChatController), the
 * same for someone's own conversation with the Assistant, the only one there.
 */
class ChatController extends Controller
{
    /** Messages a conversation opens with; earlier ones come on asking */
    private const PAGE = 50;

    public function __construct(private ChatService $chat)
    {
    }

    public function index(Request $request, ?Project $project = null): Response
    {
        $this->authorize('startChat', [Conversation::class, $project]);

        $assistant = $this->withAssistant($request->user(), $project);

        // Outside any project it is the only conversation: open from the start
        return $this->page($request, $project, $project ? null : $assistant);
    }

    public function show(Request $request, Project $project, Conversation $conversation): Response
    {
        $this->authorize('readChat', $conversation);
        $this->chat->read($conversation, $request->user());

        return $this->page($request, $project, $conversation);
    }

    /** The chat's window, which never leaves the page it opens over: all it shows, in JSON */
    public function panel(Request $request, ?Project $project = null): JsonResponse
    {
        $this->authorize('startChat', [Conversation::class, $project]);
        $this->withAssistant($request->user(), $project);

        $open = null;
        if ($request->integer('conversation')) {
            $open = Conversation::where('project_id', $project?->id)->findOrFail($request->integer('conversation'));
            $this->authorize('readChat', $open);
            $this->chat->read($open, $request->user());
        }

        return response()->json($this->data($request, $project, $open));
    }

    /** The Assistant greeted them: once, wherever they go next */
    public function welcomed(Request $request, ?Project $project = null): JsonResponse
    {
        $request->user()->forceFill(['welcomed_at' => now()])->save();

        return response()->json(['welcomed' => true]);
    }

    public function store(Request $request, Project $project): RedirectResponse|JsonResponse
    {
        $this->authorize('startChat', [Conversation::class, $project]);
        $data = $request->validate([
            'people'   => ['required', 'array', 'min:1', 'max:100'],
            'people.*' => ['integer'],
            'title'    => ['nullable', 'string', 'max:80'],
        ]);

        $conversation = $this->chat->start($project, $request->user(), $data['people'], $data['title'] ?? null);

        return $request->wantsJson()
            ? response()->json(['conversation' => $conversation->id])
            : to_route('chat.show', [$project, $conversation]);
    }

    public function send(Request $request, ?Project $project, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('writeChat', $conversation);
        abort_unless($conversation->project_id === $project?->id, 404);
        $data = $request->validate(['body' => ['required', 'string', 'max:' . ($conversation->is_assistant ? config('assistant.message_chars') : 5000)]]);

        $this->chat->send($conversation, $request->user(), trim($data['body']));

        return $request->wantsJson() ? response()->json([]) : ($project ? to_route('chat.show', [$project, $conversation]) : to_route('account.chat.index'));
    }

    /** A group's name, and people added to it */
    public function update(Request $request, Project $project, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('manageGroup', $conversation);
        $data = $request->validate([
            'title'    => ['nullable', 'string', 'max:80'],
            'people'   => ['array', 'max:100'],
            'people.*' => ['integer'],
        ]);

        $this->chat->update($conversation, $data);

        return $request->wantsJson() ? response()->json([]) : back();
    }

    public function pin(Request $request, ?Project $project, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('readChat', $conversation);
        abort_unless($conversation->project_id === $project?->id, 404);
        $this->chat->pin($conversation, $request->user(), $request->validate(['pinned' => ['required', 'boolean']])['pinned']);

        return $request->wantsJson() ? response()->json([]) : back();
    }

    public function leave(Request $request, Project $project, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('leaveGroup', $conversation);
        $this->chat->leave($conversation, $request->user());

        return $request->wantsJson() ? response()->json([]) : to_route('chat.index', $project)->with('success', __('flash.conversation_left'));
    }

    public function destroyMessage(Request $request, ?Project $project, Message $message): RedirectResponse|JsonResponse
    {
        $this->authorize('unsendMessage', [Conversation::class, $message]);
        abort_unless($message->conversation->project_id === $project?->id, 404);
        $this->chat->remove($message);

        return $request->wantsJson() ? response()->json([]) : back();
    }

    /**
     * A conversation with one of the project's admins, for whoever asks: the one they already
     * talk with, if any, or any of them. Who they are is the server's to know.
     */
    public function writeToAdmin(Request $request, Project $project, Places $places): JsonResponse
    {
        $this->authorize('startChat', [Conversation::class, $project]);
        $user = $request->user();
        $admins = $places->admins($user, $project)->pluck('users.id');
        abort_if($admins->isEmpty(), 404);

        $talked = Conversation::where('project_id', $project->id)
            ->whereIn('pair', $admins->map(fn (int $admin) => Conversation::pairOf($user->id, $admin)))
            ->latest('last_message_at')
            ->first();

        return response()->json(['conversation' => ($talked ?? $this->chat->start($project, $user, [$admins->random()]))->id]);
    }

    /**
     * A report the Assistant drafted, sent to the platform's team as it was shown: its words
     * come from the message, never from the browser. Each is sent once, a few a day.
     */
    public function report(Request $request, ?Project $project, Message $message): JsonResponse
    {
        $conversation = $message->conversation;
        abort_unless($message->from_assistant && $conversation->project_id === $project?->id && $conversation->is_assistant, 404);
        $this->authorize('readChat', $conversation);
        $index = $request->integer('action');

        return DB::transaction(function () use ($request, $project, $message, $index) {
            $message = Message::whereKey($message->id)->lockForUpdate()->first();
            $action = $message->extras['actions'][$index] ?? null;
            abort_unless(($action['type'] ?? null) === 'report', 404);

            if (! empty($action['sent'])) {
                return response()->json(['sent' => true]);
            }
            if (Report::leftToday($request->user()) === 0) {
                return response()->json(['message' => __('chat.report_limit')], 429);
            }

            $report = Report::create(['user_id' => $request->user()->id, 'project_id' => $project?->id, 'body' => $action['message']]);
            // The owner hears of it by email, and can answer by replying
            DB::afterCommit(fn () => User::where('is_owner', true)->first()?->notify(new NewReportNotice($report)));
            $extras = $message->extras;
            $extras['actions'][$index]['sent'] = true;
            $message->forceFill(['extras' => $extras])->save();

            return response()->json(['sent' => true]);
        });
    }

    /**
     * Their conversation with the Assistant, for whoever joining gave none: anyone outside a
     * project, a support account in one (where the project has the Assistant)
     */
    private function withAssistant(User $user, ?Project $project): ?Conversation
    {
        if ($project && ($user->membershipIn($project) || ! $project->hasFeature(ProjectFeature::ASSISTANT))) {
            return null;
        }

        return $this->chat->assistantFor($user, $project);
    }

    /** The conversations, the one open (if any) with its latest messages, and who may join */
    private function page(Request $request, ?Project $project, ?Conversation $open = null): Response
    {
        return inertia($project ? 'Member/Chat/Index' : 'Chat/Index', $this->data($request, $project, $open));
    }

    /** @return array<string, mixed> */
    private function data(Request $request, ?Project $project, ?Conversation $open): array
    {
        $user = $request->user();
        $conversations = Conversation::of($user, $project)
            // A few are enough to name a conversation and show its faces, however many it has
            ->with([
                'participants' => fn ($people) => $people->select('users.id', 'firstname', 'lastname', 'avatar')->limit(6),
                'lastMessage.author:id,firstname',
            ])
            ->orderByDesc('last_message_at')->orderByDesc('id')
            ->get();
        $unread = Conversation::of($user, $project)->unreadBy($user)->pluck('id')->flip();
        $pinned = DB::table('conversation_user')->where('user_id', $user->id)->whereNotNull('pinned_at')->pluck('conversation_id')->flip();

        return [
            'conversations' => $conversations->map(fn (Conversation $conversation) => [
                ...$this->summary($conversation, $user),
                'unread' => $unread->has($conversation->id),
                'pinned' => $pinned->has($conversation->id),
            ])->values(),
            'conversation' => $open ? [
                ...$this->summary($open->load('participants:id,firstname,lastname,avatar'), $user),
                // The name it was given, if any: `title` may be made up of its people
                'name'   => $open->title,
                'people' => $open->participants->map(fn (User $person) => $this->person($person))->values(),
                'can'    => [
                    'update' => $user->can('manageGroup', $open),
                    'leave'  => $user->can('leaveGroup', $open),
                ],
            ] : null,
            'messages' => $open ? $this->messages($request, $open) : null,
            // Everyone of the project one may talk with, for a new conversation (or a group's new
            // people): a window that asks again because a message came already has them
            ...($request->boolean('known_people') ? [] : ['people' => $project?->members()->whereKeyNot($user->id)
                ->orderBy('firstname')->orderBy('lastname')->get(['users.id', 'firstname', 'lastname', 'avatar'])
                ->map(fn (User $person) => $this->person($person))->values() ?? [],
            ]),
        ];
    }

    /** The latest messages, as many as shown (?count=…, more on asking), oldest first */
    private function messages(Request $request, Conversation $conversation): array
    {
        $count = min(max($request->integer('count', self::PAGE), self::PAGE), 1000);
        $page = $conversation->messages()->with('author:id,firstname,lastname,avatar')
            ->latest('id')->limit($count + 1)->get();

        return [
            'data' => $page->take($count)->reverse()->map(fn (Message $message) => [
                'id'             => $message->id,
                'body'           => $message->body,
                'author'         => $message->author ? $this->person($message->author) : null,
                'mine'           => $message->user_id === $request->user()->id,
                'from_assistant' => $message->from_assistant,
                'actions'        => $message->extras['actions'] ?? [],
                'created_at'     => $message->created_at,
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
            'title' => $conversation->is_assistant ? __('chat.assistant') : ($conversation->is_community ? __('chat.community') : ($conversation->title ?: match ($others->count()) {
                0       => __('chat.only_you'),
                1       => $others[0]->name,
                default => $others->pluck('firstname')->join(', '),
            })),
            'is_group'     => $conversation->is_group,
            'is_community' => $conversation->is_community,
            'is_assistant' => $conversation->is_assistant,
            // It has been asked, and has not said anything yet
            'answering' => $conversation->is_assistant && $last && ! $last->from_assistant && $last->created_at->isAfter(now()->subMinutes(2)),
            'faces'     => $others->take(3)->map(fn (User $person) => $this->person($person))->values(),
            'last'      => $last ? [
                'body'   => Str::limit($last->body, 90),
                'author' => $last->user_id === $user->id ? null : ($last->from_assistant ? null : $last->author?->firstname),
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
