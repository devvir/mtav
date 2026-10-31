<?php

namespace App\Services\Assistant\Brains;

use Anthropic\Client;
use App\Services\Assistant\Brain;

/**
 * Claude through its API. What never changes is cached: most of a question's price is that.
 */
class ApiBrain implements Brain
{
    public function ask(string $instructions, string $context, array $history): ?array
    {
        $client = new Client(apiKey: config('assistant.api_key'));

        $message = $client->messages->create(
            model: config('assistant.model'),
            maxTokens: config('assistant.max_tokens'),
            system: [
                ['type' => 'text', 'text' => $instructions, 'cacheControl' => ['type' => 'ephemeral']],
                ['type' => 'text', 'text' => $context],
            ],
            messages: array_map(
                fn (array $turn) => ['role' => $turn['role'], 'content' => $turn['text']],
                $history,
            ),
            outputConfig: ['format' => ['type' => 'json_schema', 'schema' => self::SCHEMA]],
        );

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                return json_decode($block->text, true);
            }
        }

        return null;
    }
}
