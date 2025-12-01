<?php

namespace App\Services\Lottery\Enums;

enum ExecutionStatus
{
    case STARTED;
    case PARTIAL;
    case COMPLETE;
}
