<?php

namespace App\Enums;

/**
 * What a project may have. The variant says which exist and which it may choose
 * (config/variants). Off, a feature is hidden and its data kept: on again, it is all there. The
 * lottery is not among them (it stays, even with events off).
 */
enum ProjectFeature: string
{
    case FORUM = 'forum';
    case CHAT = 'chat';
    case ASSISTANT = 'assistant';
    case GALLERY = 'gallery';
    case FILES = 'files';
    case EVENTS = 'events';
}
