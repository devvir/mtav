<?php

namespace App\Services\Assistant;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * The chat's guide to the app: someone writes, it answers in the same conversation, with words
 * and, at most, a few buttons leading where they could have gone by themselves. Whatever the
 * brain gives is checked here: only places on this person's list become links.
 */
class Assistant
{
    private const ACTIONS = 3;
    private const LABEL_CHARS = 60;
    private const TEXT_CHARS = 1500;

    public function __construct(private Brain $brain, private Prompt $prompt, private Places $places)
    {
    }

    public function answer(Message $question): void
    {
        $conversation = $question->conversation;
        $user = $question->author;

        if (! $user || ! $user->membershipIn($conversation->project_id)) {
            return;
        }

        // They read it in their own language, whatever the worker last spoke
        App::setLocale($user->preferredLocale() ?? config('app.locale'));

        if ($refusal = $this->overLimit($user)) {
            $this->say($conversation, __($refusal));

            return;
        }

        $answer = rescue(fn () => $this->brain->ask(
            $this->prompt->instructions($user, $conversation->project),
            $this->prompt->context($user, $conversation->project),
            $this->history($conversation),
        ), report: fn (Throwable $error) => true);

        $text = is_array($answer) ? $this->text($answer['text'] ?? null) : '';

        if ($text === '') {
            $this->say($conversation, __('chat.assistant_trouble'));

            return;
        }

        $this->say($conversation, $text, $this->actions($answer['actions'] ?? null, $user, $conversation));
    }

    /** In the conversation, like any message; to whoever has it open, at once */
    public function say(Conversation $conversation, string $text, array $actions = []): Message
    {
        $message = DB::transaction(function () use ($conversation, $text, $actions) {
            $message = new Message(['body' => $text]);
            $message->forceFill([
                'conversation_id' => $conversation->id,
                'from_assistant'  => true,
                'extras'          => $actions ? ['actions' => $actions] : null,
            ])->save();
            $conversation->forceFill(['last_message_id' => $message->id, 'last_message_at' => $message->created_at])->save();

            return $message;
        });

        rescue(fn () => MessageSent::dispatch($message, $conversation->participants()->pluck('users.id')->all()));

        return $message;
    }

    /** The words of a refusal, or nothing: it costs nothing to say no here */
    private function overLimit(User $user): ?string
    {
        if (config('assistant.driver') === 'off') {
            return 'chat.assistant_off';
        }

        $today = Message::where('created_at', '>=', now()->startOfDay())
            ->whereHas('conversation', fn ($q) => $q->where('is_assistant', true))
            ->where('from_assistant', false);

        return match (true) {
            (clone $today)->where('user_id', $user->id)->count() > config('assistant.per_person_per_day') => 'chat.assistant_limit',
            (clone $today)->count() > config('assistant.per_day')                                         => 'chat.assistant_busy',
            default                                                                                       => null,
        };
    }

    /** @return list<array{role: 'user'|'assistant', text: string}> */
    private function history(Conversation $conversation): array
    {
        return $conversation->messages()->latest('id')->limit(config('assistant.history'))->get()
            ->reverse()
            ->map(fn (Message $message) => [
                'role' => $message->from_assistant ? 'assistant' : 'user',
                'text' => Str::limit($message->body, config('assistant.message_chars')),
            ])
            ->values()
            // An exchange starts with the person
            ->skipUntil(fn (array $turn) => $turn['role'] === 'user')
            ->values()
            ->all();
    }

    private function text(mixed $text): string
    {
        return is_string($text) ? Str::limit(trim(strip_tags($text)), self::TEXT_CHARS) : '';
    }

    /**
     * What the brain offered, as far as this person may have it: a place not on their list is
     * dropped, and every address is the server's own.
     *
     * @return list<array{type: string, label: string, href?: string, tour?: string}>
     */
    private function actions(mixed $offered, User $user, Conversation $conversation): array
    {
        if (! is_array($offered)) {
            return [];
        }

        $places = $this->places->for($user, $conversation->project);
        $project = ['project' => $conversation->project_id];

        return collect($offered)
            ->map(function ($action) use ($places, $project) {
                $label = is_array($action) ? Str::limit(trim(strip_tags((string) ($action['label'] ?? ''))), self::LABEL_CHARS) : '';
                $type = is_array($action) ? ($action['type'] ?? null) : null;
                if ($label === '') {
                    return null;
                }
                if ($type === 'reply') {
                    return ['type' => 'reply', 'label' => $label];
                }

                $place = $places[$action['place'] ?? ''] ?? null;
                if (! $place || ! in_array($type, ['link', 'tour'], true) || ($type === 'tour' && ! isset($place['tour']))) {
                    return null;
                }

                return [
                    'type'  => $type,
                    'label' => $label,
                    'href'  => route($place['route'], [...$project, ...($place['params'] ?? [])], absolute: false),
                    ...($type === 'tour' ? ['tour' => $place['tour']] : []),
                ];
            })
            ->filter()
            ->take(self::ACTIONS)
            ->values()
            ->all();
    }
}
