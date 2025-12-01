<?php

namespace App\Services\Lottery\Exceptions;

use App\Models\Event;

class CannotExecuteLotteryException extends LotteryExecutionException
{
    public function __construct(public readonly Event $lottery)
    {
        $json = json_encode($lottery->withoutRelations()->getAttributes());

        parent::__construct("Cannot execute lottery due to its current state: {$json}");
    }

    public function getUserMessage(): string
    {
        return match (true) {
            ! $this->lottery->isPublished()    => __('lottery.already_executed_or_executing'),
            ! $this->lottery->start_date       => __('lottery.no_date_set'),
            $this->lottery->start_date > now() => __('lottery.not_yet_scheduled'),
            default                            => __('lottery.cannot_execute_generic'),
        };
    }
}
