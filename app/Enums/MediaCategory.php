<?php

namespace App\Enums;

enum MediaCategory: string
{
    case AUDIO = 'audio';
    case DOCUMENT = 'document';
    case IMAGE = 'image';
    case VIDEO = 'video';

    /** Not stored: a filter meaning photos, videos and audio (the media tab; documents have theirs) */
    case MEDIA = 'media';
}
