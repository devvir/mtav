<?php

namespace App\Services\Lottery\Exceptions;

/**
 * Exception thrown when GLPK execution times out.
 */
class GlpkTimeoutException extends GlpkException
{
    public function getUserMessage(): string
    {
        return __('lottery.glpk_timeout');
    }
}
