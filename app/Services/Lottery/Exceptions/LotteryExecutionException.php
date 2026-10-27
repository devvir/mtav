<?php

namespace App\Services\Lottery\Exceptions;

use RuntimeException;

/**
 * A lottery operation that could not proceed. getUserMessage() is what people see;
 * the exception message is for logs.
 */
class LotteryExecutionException extends RuntimeException
{
    protected string $userMessageKey = 'lottery.execution_failed';

    protected array $userMessageParams = [];

    public static function withUserMessage(string $key, array $params = [], string $message = ''): static
    {
        $exception = new static($message ?: $key);
        $exception->userMessageKey = $key;
        $exception->userMessageParams = $params;

        return $exception;
    }

    public function getUserMessage(): string
    {
        return __($this->userMessageKey, $this->userMessageParams);
    }
}
