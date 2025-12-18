<?php

namespace App\Services\Lottery\Enums;

enum LotteryAuditType: string
{
    case INIT = 'init';
    case GROUP_EXECUTION = 'group_execution';
    case PROJECT_EXECUTION = 'project_execution';
    case CUSTOM = 'custom';
    case INVALIDATE = 'invalidate';
    case FAILURE = 'failure';
}
