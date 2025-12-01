<?php

namespace App\Services\Lottery\Exceptions;

use Exception;

class LockedLotteryPreferencesException extends Exception
{
    public function getUserMessage(): string
    {
        return __('lottery.preferences_locked');
    }
}
