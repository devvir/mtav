<?php

namespace App\Http\Presenters;

use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Http\Resources\PersonResource;
use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Http\Request;

/**
 * What the chat shows someone, its page and its window alike: their conversations (pinned and
 * new to them marked), the one open (if any) with its latest messages, and who they may talk
 * with. A project's chat, or with none, the one outside any.
 */
class ChatPresenter
{
    /** Messages a conversation opens with; earlier ones come on asking */
    private const PAGE = 50;

    /** The most messages shown at once, however many are asked for */
    private const MOST_MESSAGES = 1000;

    /** A few people are enough to name a conversation and show its faces, however many it has */
    private const FACES = 6;

    /** @return array<string, mixed> */
    public function chat(Request $request, ?Project $project, ?Conversation $open): array
    {
        return [
            'conversations' => $this->conversations($request, $project),
            'conversation'  => $open ? $this->open($request, $open) : null,
            'messages'      => $open ? $this->messages($request, $open) : null,
            // A window that asks again because a message came already has them
            ...($request->boolean('known_people') ? [] : ['people' => $this->people($request, $project)]),
        ];
    }

    private function conversations(Request $request, ?Project $project): array
    {
        $user = $request->user();
        $conversations = Conversation::of($user, $project)
            ->withPlaceOf($user)
            ->with([
                'participants' => fn ($people) => $people->select('users.id', 'firstname', 'lastname', 'avatar')->limit(self::FACES),
                'lastMessage.author:id,firstname',
            ])
            ->orderByDesc('last_message_at')->orderByDesc('id')
            ->get();

        return ConversationResource::collection($conversations)->resolve($request);
    }

    private function open(Request $request, Conversation $open): array
    {
        $user = $request->user();

        return [
            ...(new ConversationResource($open->load('participants:id,firstname,lastname,avatar')))->resolve($request),
            // The name it was given, if any: `title` may be made up of its people
            'name'   => $open->title,
            'people' => PersonResource::collection($open->participants)->resolve($request),
            'can'    => [
                'update' => $user->can('manageGroup', $open),
                'leave'  => $user->can('leaveGroup', $open),
            ],
        ];
    }

    /** The latest messages, as many as shown (?count=…, more on asking), oldest first */
    private function messages(Request $request, Conversation $conversation): array
    {
        $count = min(max($request->integer('count', self::PAGE), self::PAGE), self::MOST_MESSAGES);
        $page = $conversation->messages()->with('author:id,firstname,lastname,avatar')
            ->latest('id')->limit($count + 1)->get();

        return [
            'data'    => MessageResource::collection($page->take($count)->reverse()->values())->resolve($request),
            'earlier' => $page->count() > $count,
        ];
    }

    /** Everyone of the project one may talk with, for a new conversation (or a group's new people) */
    private function people(Request $request, ?Project $project): array
    {
        if (! $project) {
            return [];
        }

        return PersonResource::collection($project->members()->whereKeyNot($request->user()->id)
            ->orderBy('firstname')->orderBy('lastname')->get(['users.id', 'firstname', 'lastname', 'avatar']))->resolve($request);
    }
}
