<?php

namespace App\Services\Lottery\Exceptions;

/**
 * The run would go ahead with something unusual, which the admin confirms first: they send
 * `option` back with the run to go ahead. The message says what it is.
 */
class LotteryRequiresConfirmationException extends LotteryExecutionException
{
    public function __construct(public readonly string $option, string $message = '')
    {
        parent::__construct($message);
    }

    public function getUserMessage(): string
    {
        return $this->message ?: parent::getUserMessage();
    }
}
