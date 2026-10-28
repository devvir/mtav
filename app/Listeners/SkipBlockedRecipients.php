<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Address;

/**
 * Mail never goes to addresses under the blocked domains (config mail.blocked_domains):
 * on staging, the demo accounts' made-up ones. They are left out and logged; a message
 * left with nobody to go to is not sent.
 */
class SkipBlockedRecipients
{
    public function handle(MessageSending $event): ?bool
    {
        $blocked = config('mail.blocked_domains');
        if (! $blocked) {
            return null;
        }

        $message = $event->message;
        $skipped = [];
        foreach (['to' => $message->getTo(), 'cc' => $message->getCc(), 'bcc' => $message->getBcc()] as $field => $addresses) {
            $kept = array_filter($addresses, fn (Address $address) => ! self::isBlocked($address->getAddress(), $blocked));
            $skipped = [...$skipped, ...array_map(fn (Address $address) => $address->getAddress(), array_diff_key($addresses, $kept))];
            $message->{$field}(...$kept);
        }

        if ($skipped) {
            Log::info('Mail not sent to blocked addresses', ['subject' => $message->getSubject(), 'skipped' => $skipped]);
        }

        return $message->getTo() || $message->getCc() || $message->getBcc() ? null : false;
    }

    /**
     * Under a blocked domain: the domain itself or one below it ("example.com"), or any
     * name ending so (".test")
     */
    private static function isBlocked(string $address, array $domains): bool
    {
        $domain = strtolower(substr(strrchr($address, '@') ?: '', 1));

        foreach ($domains as $blocked) {
            $blocked = strtolower($blocked);
            if (str_starts_with($blocked, '.')
                ? str_ends_with($domain, $blocked)
                : $domain === $blocked || str_ends_with($domain, ".{$blocked}")) {
                return true;
            }
        }

        return false;
    }
}
