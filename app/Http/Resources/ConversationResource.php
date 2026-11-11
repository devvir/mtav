<?php

namespace App\Http\Resources;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * A conversation as its row in someone's chat: what it is called for them, the faces of the
 * others, its last message, and (when the list asked) whether it is new to them and pinned.
 * Its people and last message come loaded.
 *
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    /** How long an Assistant's answer may take before the chat stops showing it is writing */
    private const ANSWERING_MINUTES = 2;

    public function toArray(Request $request): array
    {
        /** @var User $viewer */
        $viewer = $request->user();
        $others = $this->participants->reject(fn (User $person) => $person->id === $viewer->id)->values();
        $last = $this->lastMessage;

        return [
            'id'           => $this->id,
            'title'        => $this->titleFor($others),
            'is_group'     => $this->is_group,
            'is_community' => $this->is_community,
            'is_assistant' => $this->is_assistant,
            // It has been asked, and has not said anything yet
            'answering' => $this->is_assistant && $last && ! $last->from_assistant && $last->created_at->isAfter(now()->subMinutes(self::ANSWERING_MINUTES)),
            'faces'     => PersonResource::collection($others->take(3)->values())->resolve($request),
            'last'      => $last ? [
                'body'   => Str::limit($last->body, 90),
                'author' => $last->user_id === $viewer->id || $last->from_assistant ? null : $last->author?->firstname,
                'mine'   => $last->user_id === $viewer->id,
                'at'     => $last->created_at,
            ] : null,
            'unread' => $this->whenHas('unread'),
            'pinned' => $this->whenHas('pinned'),
        ];
    }

    /**
     * A group by its name, else by its people; one on one, by the other person
     *
     * @param Collection<int, User> $others */
    private function titleFor(Collection $others): string
    {
        return match (true) {
            $this->is_assistant => __('chat.assistant'),
            $this->is_community => __('chat.community'),
            (bool) $this->title => $this->title,
            default             => match ($others->count()) {
                0       => __('chat.only_you'),
                1       => $others[0]->name,
                default => $others->pluck('firstname')->join(', '),
            },
        };
    }
}
