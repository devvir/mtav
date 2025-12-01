<?php

namespace App\Services\Lottery\Exceptions;

use Exception;

class LockedLotteryException extends Exception
{
    public function getUserMessage(): string
    {
        return __('lottery.lottery_locked');
    }
}
