<?php

namespace App\Http\Props;

use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;

/**
 * The chat the person's own pages carry (resources/js/layouts/AccountLayout.tsx): framed by a
 * project (the one last browsed, or their only one), that project's; with none, their own with
 * the Assistant, as on its own page. One per request: the framing is looked up once.
 */
class ChatProps
{
    /** The person's own pages, framed by a project */
    private const FRAMED = ['profile.*', 'notifications.*', 'directory.*', 'documentation.*', 'projects.index', 'projects.create', 'account.chat.*'];

    private bool $framed = false;

    private ?Project $framing = null;

    public function __construct(private Request $request, private ProjectProps $projects, private ChatService $chat)
    {
    }

    /** On those pages, the project whose chat they carry: the same frame's */
    public function chatProject(User $user): ?array
    {
        if (! $this->request->routeIs(self::FRAMED) || $this->request->routeIs('account.chat.*')) {
            return null;
        }
        $framing = $this->framing($user);

        return $framing ? $this->projects->of($framing, $user) : null;
    }

    /**
     * On those pages with no project framing them (and on its own page): theirs with the
     * Assistant. Someone's first arrival (signed up, invited nowhere) opens it to greet them.
     *
     * @return array{unread: int, welcome: int|null}|null
     */
    public function accountChat(User $user): ?array
    {
        if (! $this->request->routeIs(self::FRAMED) || (! $this->request->routeIs('account.chat.*') && $this->framing($user))) {
            return null;
        }

        return [
            'unread'  => Conversation::of($user, null)->unreadBy($user)->count(),
            'welcome' => ChatService::welcomes($user) ? $this->chat->assistantFor($user)->id : null,
        ];
    }

    /** The one last browsed, or their only one (the account layout frames them the same way) */
    private function framing(User $user): ?Project
    {
        if (! $this->framed) {
            $projects = $user->reachableProjects();
            $this->framing = (clone $projects)->find($this->request->session()->get('last_project'))
                ?? ((clone $projects)->count() === 1 ? $projects->first() : null);
            $this->framed = true;
        }

        return $this->framing;
    }
}
