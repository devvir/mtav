<?php

namespace App\Enums;

enum MediaCategory: string
{
    case AUDIO = 'audio';
    case DOCUMENT = 'document';
    case IMAGE = 'image';
    case VIDEO = 'video';

    /** Not stored: a filter meaning images and videos (the gallery) */
    case VISUAL = 'visual';
}
