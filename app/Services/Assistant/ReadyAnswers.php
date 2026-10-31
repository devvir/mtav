<?php

namespace App\Services\Assistant;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Support\Str;

/**
 * What needs no model: greetings, thanks, and the questions the Assistant itself offers with a
 * tap. Written beforehand, or worked out from the project, they are free and always the same.
 * The model is there for what people put in their own words.
 */
class ReadyAnswers
{
    public function __construct(private ProjectService $projects)
    {
    }

    /** @return array{text: string, actions: list<array{type: string, label: string, place: string}>}|null */
    public function to(string $words, User $user, Project $project): ?array
    {
        $said = $this->plain($words);

        return match (true) {
            $this->isAny($said, __('chat.ready.hello_words'))        => $this->answer(__('chat.ready.hello')),
            $this->isAny($said, __('chat.ready.thanks_words'))       => $this->answer(__('chat.ready.thanks')),
            $said === $this->plain(__('What can I do here?'))        => $this->whatCanIDo($user, $project),
            $said === $this->plain(__('How does the lottery work?')) => $this->answer(__('chat.ready.lottery'), ['tour', 'lottery', __('chat.ready.lottery_action')]),
            $said === $this->plain(__('How do I rank the units?'))   => $user->can('rankUnits', $project) ? $this->answer(__('chat.ready.rank'), ['link', 'lottery', __('chat.ready.rank_action')]) : null,
            $said === $this->plain(__('How do I invite my family?')) => $user->familyIn($project) ? $this->answer(__('chat.ready.invite'), ['link', 'invite_family', __('chat.ready.invite_action')]) : null,
            $said === $this->plain(__('What is left to set up?'))    => $user->manages($project) ? $this->leftToSetUp($project) : null,
            default                                                  => null,
        };
    }

    private function whatCanIDo(User $user, Project $project): array
    {
        return $this->answer(
            collect([
                __('chat.ready.can_do'),
                $user->can('rankUnits', $project) ? __('chat.ready.can_do_rank') : null,
                $user->manages($project) ? __('chat.ready.can_do_manage') : null,
            ])->filter()->join(' '),
            ['tour', 'home', __('chat.ready.can_do_action')],
        );
    }

    private function leftToSetUp(Project $project): array
    {
        $left = collect($this->projects->setupChecklist($project))
            ->reject(fn (bool $done) => $done)
            ->keys()
            ->map(fn (string $step) => __("chat.ready.steps.{$step}"));

        return $this->answer(
            $left->isEmpty() ? __('chat.ready.setup_done') : __('chat.ready.setup_left', ['steps' => $left->join(', ')]),
            ['link', 'setup', __('chat.ready.setup_action')],
        );
    }

    /** @param array{string, string, string} ...$actions type, place and label */
    private function answer(string $text, array ...$actions): array
    {
        return [
            'text'    => $text,
            'actions' => array_map(fn (array $action) => ['type' => $action[0], 'place' => $action[1], 'label' => $action[2]], $actions),
        ];
    }

    /** @param string $words Each way to say it, between bars: as many as the language has */
    private function isAny(string $said, string $words): bool
    {
        return in_array($said, array_map($this->plain(...), explode('|', $words)), true);
    }

    /** Whatever the capitals, accents and punctuation */
    private function plain(string $words): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($words))));
    }
}
