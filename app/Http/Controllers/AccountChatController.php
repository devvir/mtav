<?php

namespace App\Http\Controllers;

use App\Http\Presenters\ChatPresenter;
use App\Http\Requests\Chat\MessageRequest;
use App\Http\Requests\Chat\PanelRequest;
use App\Http\Requests\Chat\ReportRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * The chat outside any project (on someone's own pages, in none yet or none chosen): their
 * conversation with the Assistant, the same chat a project has, with nobody else in it, open
 * from the start
 */
class AccountChatController extends Controller
{
    public function __construct(private ChatService $chat, private ChatPresenter $presenter)
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('startChat', [Conversation::class, null]);

        return inertia('Chat/Index', $this->presenter->chat($request, null, $this->chat->assistantFor($request->user())));
    }

    /** The chat's window, which never leaves the page it opens over: all it shows, in JSON */
    public function panel(PanelRequest $request): JsonResponse
    {
        $this->authorize('startChat', [Conversation::class, null]);
        $open = $this->chat->assistantFor($request->user());
        $this->chat->read($open, $request->user());
        if ($asked = $request->asked()) {
            $this->chat->read($open = $asked, $request->user());
        }

        return response()->json($this->presenter->chat($request, null, $open));
    }

    /** The Assistant greeted them: once, wherever they go next */
    public function welcomed(Request $request): JsonResponse
    {
        $request->user()->forceFill(['welcomed_at' => now()])->save();

        return response()->json(['welcomed' => true]);
    }

    public function send(MessageRequest $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->chat->post($conversation, $request->user(), $request->body());

        return $request->wantsJson() ? response()->json([]) : to_route('account.chat.index');
    }

    public function pin(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('readChat', $conversation);
        abort_unless($conversation->project_id === null, 404);
        $this->chat->pin($conversation, $request->user(), $request->validate(['pinned' => ['required', 'boolean']])['pinned']);

        return $request->wantsJson() ? response()->json([]) : back();
    }

    public function updateMessage(MessageRequest $request, Message $message): RedirectResponse|JsonResponse
    {
        $this->chat->edit($message, $request->body());

        return $request->wantsJson() ? response()->json([]) : back();
    }

    public function destroyMessage(Request $request, Message $message): RedirectResponse|JsonResponse
    {
        abort_unless($message->conversation->project_id === null, 404);
        $this->authorize('unsendMessage', [Conversation::class, $message]);
        $this->chat->remove($message);

        return $request->wantsJson() ? response()->json([]) : back();
    }

    /** A report the Assistant drafted, sent to the platform's team (ReportService) */
    public function report(ReportRequest $request, Message $message, ReportService $reports): JsonResponse
    {
        return $reports->send($message, $request->action(), $request->user())
            ? response()->json(['sent' => true])
            : response()->json(['message' => __('chat.report_limit')], 429);
    }
}
