<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum MediaCategory: string
{
    case AUDIO = 'audio';
    case DOCUMENT = 'document';
    case IMAGE = 'image';
    case UNKNOWN = 'unknown';
    case VIDEO = 'video';
    case VISUAL = 'visual';

    public static function labels(): array
    {
        return Arr::pluck(self::cases(), fn ($v) => $v->label(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::AUDIO    => __('Audio'),
            self::DOCUMENT => __('Document'),
            self::IMAGE    => __('Image'),
            self::UNKNOWN  => __('Unknown'),
            self::VIDEO    => __('Video'),
            self::VISUAL   => __('Visual'),
        };
    }
}
