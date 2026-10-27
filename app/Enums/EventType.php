<?php

namespace App\Enums;

enum EventType: string
{
    case LOTTERY = 'lottery';
    case ONLINE = 'online';
    case ONSITE = 'onsite';
}
