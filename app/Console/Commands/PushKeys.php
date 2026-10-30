<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class PushKeys extends Command
{
    protected $signature = 'push:keys';

    protected $description = 'Make a pair of VAPID keys, for notifications on devices: lines for the .env';

    public function handle(): int
    {
        ['publicKey' => $public, 'privateKey' => $private] = VAPID::createVapidKeys();

        $this->line("VAPID_PUBLIC_KEY={$public}");
        $this->line("VAPID_PRIVATE_KEY={$private}");

        return self::SUCCESS;
    }
}
