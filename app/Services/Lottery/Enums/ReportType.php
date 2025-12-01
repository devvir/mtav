<?php

namespace App\Services\Lottery\Enums;

enum ReportType
{
    case PHASE_1_START;
    case PHASE_1_COMPLETE;

    case PHASE_2_START;
    case PHASE_2_COMPLETE;

    case PHASE_3_START;
    case PHASE_3_COMPLETE;

    case EXECUTION_COMPLETE;
}
