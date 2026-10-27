<?php

namespace App\Enums;

enum LotteryRunStatus: string
{
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case INVALIDATED = 'invalidated';
}
