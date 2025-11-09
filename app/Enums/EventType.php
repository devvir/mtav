<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum EventType: string
{
    case LOTTERY = 'lottery';
    case ONLINE = 'online';
    case ONSITE = 'onsite';

    public static function labels(): array
    {
        return Arr::pluck(self::cases(), fn ($v) => $v->label(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::LOTTERY => __('Lottery'),
            self::ONLINE => __('Online'),
            self::ONSITE => __('On-site'),
        };
    }
}
