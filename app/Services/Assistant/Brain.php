<?php

namespace App\Services\Assistant;

/**
 * What answers. It gets words and gives words: no tools, no internet, nothing of the database
 * but what the Assistant chose to put in the prompt.
 */
interface Brain
{
    /**
     * The shape every answer comes in. Places are a closed list, so that a link can only ever
     * lead where the server says.
     */
    public const SCHEMA = [
        'type'       => 'object',
        'properties' => [
            'text'    => ['type' => 'string'],
            'actions' => [
                'type'  => 'array',
                'items' => [
                    'type'       => 'object',
                    'properties' => [
                        'type'  => ['type' => 'string', 'enum' => ['link', 'tour', 'reply']],
                        'label' => ['type' => 'string'],
                        'place' => ['type' => 'string'],
                    ],
                    'required'             => ['type', 'label', 'place'],
                    'additionalProperties' => false,
                ],
            ],
        ],
        'required'             => ['text', 'actions'],
        'additionalProperties' => false,
    ];

    /**
     * @param string $instructions  What never changes: who it is, its rules, the guides
     * @param string $context       About whoever asks, today
     * @param list<array{role: 'user'|'assistant', text: string}> $history  The latest last
     *
     * @return array{text?: mixed, actions?: mixed}|null  null when there is no answer
     */
    public function ask(string $instructions, string $context, array $history): ?array;
}
