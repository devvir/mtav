<?php

namespace App\Enums;

enum MediaCategory: string
{
    case AUDIO = 'audio';
    case DOCUMENT = 'document';
    case IMAGE = 'image';
    case VIDEO = 'video';

    /** Not stored: a filter meaning photos and videos (the gallery) */
    case MEDIA = 'media';

    /** Not stored: a filter meaning documents and audio (the files tab: the project's records) */
    case FILES = 'files';

    /** The project feature this kind of file belongs to */
    public function feature(): ProjectFeature
    {
        return in_array($this, [self::IMAGE, self::VIDEO, self::MEDIA], true) ? ProjectFeature::GALLERY : ProjectFeature::FILES;
    }
}
