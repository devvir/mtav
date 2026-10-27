<?php

namespace App\Services\Lottery\Exceptions;

/**
 * The lottery has run (or is running): its schedule and the families' rankings no
 * longer change.
 */
class LotteryLockedException extends LotteryExecutionException
{
    protected string $userMessageKey = 'lottery.locked';
}
