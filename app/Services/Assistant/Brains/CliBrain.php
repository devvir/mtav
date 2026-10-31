<?php

namespace App\Services\Assistant\Brains;

use App\Services\Assistant\Brain;
use Illuminate\Support\Facades\Process;

/**
 * Claude through its command line, on a signed-in account: for a developer trying it, never in
 * production (config/assistant.php). It runs with no tools and none of the account's settings or memory: words in, words out.
 */
class CliBrain implements Brain
{
    public function ask(string $instructions, string $context, array $history): ?array
    {
        $transcript = collect($history)
            ->map(fn (array $turn) => ($turn['role'] === 'user' ? 'Person: ' : 'Assistant: ') . $turn['text'])
            ->join("\n\n");

        $result = Process::timeout(config('assistant.timeout'))
            ->path('/tmp')
            ->env(array_filter(['HOME' => config('assistant.cli_home'), 'DISABLE_AUTOUPDATER' => '1']))
            ->run([
                'claude', '-p', "The conversation so far:\n\n{$transcript}\n\nAnswer the person's last message.",
                '--model', config('assistant.cli_model'),
                '--output-format', 'json',
                '--max-turns', '4',
                '--no-session-persistence',
                '--system-prompt', "{$instructions}\n\n{$context}",
                '--setting-sources', '',
                '--json-schema', json_encode(self::SCHEMA),
                '--tools', '',
            ]);

        $answer = json_decode($result->output(), true);

        return is_array($answer) && empty($answer['is_error']) ? ($answer['structured_output'] ?? null) : null;
    }
}
