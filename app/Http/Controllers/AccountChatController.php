<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Member\ChatController;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * The chat outside any project (on someone's own pages, in none yet or none chosen): their
 * conversation with the Assistant, the same chat a project has, with nobody else in it
 */
class AccountChatController extends Controller
{
    public function __construct(private ChatController $chat)
    {
    }

    public function index(Request $request): Response
    {
        return $this->chat->index($request, null);
    }

    public function panel(Request $request): JsonResponse
    {
        return $this->chat->panel($request, null);
    }

    public function welcomed(Request $request): JsonResponse
    {
        return $this->chat->welcomed($request, null);
    }

    public function send(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        return $this->chat->send($request, null, $conversation);
    }

    public function pin(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        return $this->chat->pin($request, null, $conversation);
    }

    public function destroyMessage(Request $request, Message $message): RedirectResponse|JsonResponse
    {
        return $this->chat->destroyMessage($request, null, $message);
    }

    public function report(Request $request, Message $message): JsonResponse
    {
        return $this->chat->report($request, null, $message);
    }
}
