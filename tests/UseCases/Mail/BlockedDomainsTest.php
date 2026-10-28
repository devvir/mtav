<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Mail skips the blocked domains (staging's demo accounts) and reaches everyone else.
 */

beforeEach(function () {
    config(['mail.blocked_domains' => ['example.com', '.test']]);
});

function sendTo(array $to, array $cc = []): void
{
    Mail::raw('Hello', function ($message) use ($to, $cc) {
        $message->to($to)->subject('Welcome');
        if ($cc) {
            $message->cc($cc);
        }
    });
}

function delivered(): array
{
    return app('mailer')->getSymfonyTransport()->messages()
        ->map(fn ($sent) => array_map(fn ($address) => $address->getAddress(), $sent->getOriginalMessage()->getTo()))
        ->all();
}

it('sends to real addresses and leaves out the blocked ones', function () {
    Log::spy();

    sendTo(['ana@udelar.edu.uy', 'demo@example.com', 'x@sub.example.com', 'y@school.test']);

    expect(delivered())->toBe([['ana@udelar.edu.uy']]);
    Log::shouldHaveReceived('info')->withArgs(fn ($text, $context) => $context['skipped'] === ['demo@example.com', 'x@sub.example.com', 'y@school.test']);
});

it('sends nothing when every address is blocked', function () {
    sendTo(['demo@example.com'], cc: ['other@example.com']);

    expect(delivered())->toBe([]);
});

it('does not mistake a lookalike domain for a blocked one', function () {
    sendTo(['ana@notexample.com']);

    expect(delivered())->toBe([['ana@notexample.com']]);
});

it('sends to everyone when nothing is blocked', function () {
    config(['mail.blocked_domains' => []]);

    sendTo(['demo@example.com']);

    expect(delivered())->toBe([['demo@example.com']]);
});
