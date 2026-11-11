<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Presenters\ChatPresenter;
use App\Http\Requests\Chat\ConversationRequest;
use App\Http\Requests\Chat\MessageRequest;
use App\Http\Requests\Chat\PanelRequest;
use App\Http\Requests\Chat\ReportRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use App\Services\Assistant\Places;
use App\Services\ChatService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * A project's chat: someone's conversations (the latest first), one of them open, and
 * starting new ones with anyone of the project. Outside any project, AccountChatController.
 */
class ChatController extends Controller
{
    public function __construct(private ChatService $chat, private ChatPresenter $presenter)
    {
    }

    public function index(Request $request, Project $project): Response
    {
        $this->authorize('startChat', [Conversation::class, $project]);
        $this->chat->assistantWithoutMembership($request->user(), $project);

        return inertia('Member/Chat/Index', $this->presenter->chat($request, $project, null));
    }

    public function show(Request $request, Project $project, Conversation $conversation): Response
    {
        $this->authorize('readChat', $conversation);
        $this->chat->read($conversation, $request->user());

        return inertia('Member/Chat/Index', $this->presenter->chat($request, $project, $conversation));
    }

    /** The chat's window, which never leaves the page it opens over: all it shows, in JSON */
    public function panel(PanelRequest $request, Project $project): JsonResponse
    {
        $this->authorize('startChat', [Conversation::class, $project]);
        $this->chat->assistantWithoutMembership($request->user(), $project);
        $open = $request->asked();
        $open && $this->chat->read($open, $request->user());

        return response()->json($this->presenter->chat($request, $project, $open));
    }

    /** The Assistant greeted them: once, wherever they go next */
    public function welcomed(Request $request, Project $project): JsonResponse
    {
        $request->user()->forceFill(['welcomed_at' => now()])->save();

        return response()->json(['welcomed' => true]);
    }

    public function store(ConversationRequest $request, Project $project): RedirectResponse|JsonResponse
    {
        $conversation = $this->chat->start($project, $request->user(), $request->validated('people'), $request->validated('title'));

        return $request->wantsJson()
            ? response()->json(['conversation' => $conversation->id])
            : to_route('chat.show', [$project, $conversation]);
    }

    public function send(MessageRequest $request, Project $project, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->chat->post($conversation, $request->user(), $request->body());

        return $request->wantsJson() ? response()->json([]) : to_route('chat.show', [$project, $conversation]);
    }

    /** A group's name, and people added to it */
    public function update(ConversationRequest $request, Project $project, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->chat->update($conversation, $request->validated());

        return $request->wantsJson() ? response()->json([]) : back();
    }

    public function pin(Request $request, Project $project, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('readChat', $conversation);
        abort_unless($conversation->project_id === $project->id, 404);
        $this->chat->pin($conversation, $request->user(), $request->validate(['pinned' => ['required', 'boolean']])['pinned']);

        return $request->wantsJson() ? response()->json([]) : back();
    }

    public function leave(Request $request, Project $project, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('leaveGroup', $conversation);
        $this->chat->leave($conversation, $request->user());

        return $request->wantsJson() ? response()->json([]) : to_route('chat.index', $project)->with('success', __('flash.conversation_left'));
    }

    /** Their own message, reworded within the hour; never in the Assistant's conversation */
    public function updateMessage(MessageRequest $request, Project $project, Message $message): RedirectResponse|JsonResponse
    {
        $this->chat->edit($message, $request->body());

        return $request->wantsJson() ? response()->json([]) : back();
    }

    /** Their own message, deleted within the hour (with the Assistant, its answer too) */
    public function destroyMessage(Request $request, Project $project, Message $message): RedirectResponse|JsonResponse
    {
        abort_unless($message->conversation->project_id === $project->id, 404);
        $this->authorize('unsendMessage', [Conversation::class, $message]);
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

    /** A report the Assistant drafted, sent to the platform's team (ReportService) */
    public function report(ReportRequest $request, Project $project, Message $message, ReportService $reports): JsonResponse
    {
        return $reports->send($message, $request->action(), $request->user())
            ? response()->json(['sent' => true])
            : response()->json(['message' => __('chat.report_limit')], 429);
    }
}
