<?php

namespace App\Services\Assistant;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;
use Throwable;

/**
 * The chat's guide to the app: someone writes, it answers in the same conversation, with words
 * and, at most, a few buttons leading where they could have gone by themselves. Whatever the
 * brain gives is checked here: only places on this person's list become links.
 */
class Assistant
{
    use Localizable;

    private const ACTIONS = 3;
    private const LABEL_CHARS = 60;
    private const TEXT_CHARS = 1500;
    private const REPORT_CHARS = 1500;
    private const RESTING = 'assistant.resting';
    private const REST = 900;
    private const STUMBLES = 'assistant.stumbles';
    private const STUMBLES_FOR = 600;
    private const STUMBLES_TO_REST = 3;

    public function __construct(private Brain $brain, private Prompt $prompt, private Places $places, private ReadyAnswers $ready)
    {
    }

    public function answer(Message $question): void
    {
        $conversation = $question->conversation;
        $user = $question->author;

        if (! $user || ! $user->membershipIn($conversation->project_id)) {
            return;
        }

        // They read it in their own language; the worker's next job is told in its own
        $this->withLocale($user->preferredLocale() ?? config('app.locale'), fn () => $this->answerTo($question, $conversation, $user));
    }

    private function answerTo(Message $question, Conversation $conversation, User $user): void
    {
        // Written again before the answer came: the later message answers them all, for one ask
        if ($conversation->messages()->where('id', '>', $question->id)->where('from_assistant', false)->exists()) {
            $this->free($question);

            return;
        }

        if ($ready = $this->ready->to($question->body, $user, $conversation->project)) {
            $this->free($question);
            $this->say($conversation, $ready['text'], $this->actions($ready['actions'], $user, $conversation));

            return;
        }

        if ($refusal = $this->overLimit($user, $conversation->project)) {
            $this->free($question);
            $this->say($conversation, __($refusal), $this->toTheGuide($user, $conversation));

            return;
        }

        if (Cache::has(self::RESTING)) {
            $this->free($question);
            $this->say($conversation, __('chat.assistant_resting'), $this->toTheGuide($user, $conversation));

            return;
        }

        try {
            $answer = $this->brain->ask(
                $this->prompt->instructions($user, $conversation->project),
                $this->prompt->context($user, $conversation->project),
                $this->history($conversation),
            );
        } catch (Throwable $error) {
            report($error);
            $this->free($question);
            $this->stumbled();
            $this->say($conversation, __('chat.assistant_trouble'), $this->toTheGuide($user, $conversation));

            return;
        }

        $text = is_array($answer) ? $this->text($answer['text'] ?? null) : '';

        // It was reached and made nothing of this question: that is this person's, not everyone's
        if ($text === '') {
            $this->say($conversation, __('chat.assistant_trouble'), $this->toTheGuide($user, $conversation));

            return;
        }

        Cache::forget(self::STUMBLES);

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

    /**
     * Unreachable again and again (spent, down, a bad key: all alike from here), it rests: nobody
     * waits for an answer that will not come, and whatever is failing is left alone a while
     */
    private function stumbled(): void
    {
        Cache::add(self::STUMBLES, 0, self::STUMBLES_FOR);
        if (Cache::increment(self::STUMBLES) >= self::STUMBLES_TO_REST) {
            Cache::put(self::RESTING, true, self::REST);
            Cache::forget(self::STUMBLES);
        }
    }

    private function toTheGuide(User $user, Conversation $conversation): array
    {
        return $this->actions(
            [['type' => 'link', 'place' => 'guide', 'label' => __('chat.assistant_guide')]],
            $user,
            $conversation,
        );
    }

    /** It reached no model: it takes nothing from the person's messages of the day */
    private function free(Message $question): void
    {
        $question->forceFill(['extras' => ['free' => true]])->save();
    }

    /** The words of a refusal, or nothing: it costs nothing to say no here */
    private function overLimit(User $user, Project $project): ?string
    {
        if (config('assistant.driver') === 'off') {
            return 'chat.assistant_off';
        }

        $today = Message::where('created_at', '>=', now()->startOfDay())
            ->whereHas('conversation', fn ($q) => $q->where('is_assistant', true))
            ->where('from_assistant', false)
            ->whereNull('extras');
        // A limited project's people have half
        $theirs = (int) config('assistant.per_person_per_day');
        $theirs = $project->isApproved() ? $theirs : intdiv($theirs, 2);

        return match (true) {
            (clone $today)->where('user_id', $user->id)->count() > $theirs => 'chat.assistant_limit',
            (clone $today)->count() > config('assistant.per_day')          => 'chat.assistant_busy',
            default                                                        => null,
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
     * @return list<array{type: string, label: string, href?: string, tour?: string, message?: string}>
     */
    private function actions(mixed $offered, User $user, Conversation $conversation): array
    {
        if (! is_array($offered)) {
            return [];
        }

        $places = $this->places->for($user, $conversation->project);
        $admins = $this->places->hasAdmins($user, $conversation->project);
        $reports = Report::leftToday($user) > 0;
        $project = ['project' => $conversation->project_id];

        return collect($offered)
            ->map(function ($action) use ($places, $admins, $reports, $project) {
                $label = is_array($action) ? Str::limit(trim(strip_tags((string) ($action['label'] ?? ''))), self::LABEL_CHARS) : '';
                $type = is_array($action) ? ($action['type'] ?? null) : null;
                if ($label === '') {
                    return null;
                }
                if ($type === 'reply') {
                    return ['type' => 'reply', 'label' => $label];
                }
                // Writing to an admin: which one, the server says when it is tapped
                if ($type === 'message') {
                    return $admins ? ['type' => 'message', 'label' => $label] : null;
                }
                // Words for the platform's team, sent only if the person taps it
                if ($type === 'report') {
                    $words = Str::limit(trim(strip_tags((string) ($action['message'] ?? ''))), self::REPORT_CHARS);

                    return $reports && $words !== '' ? ['type' => 'report', 'label' => $label, 'message' => $words] : null;
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
