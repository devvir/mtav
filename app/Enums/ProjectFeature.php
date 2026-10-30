<?php

namespace App\Enums;

/**
 * What a project may turn on or off. Off, a feature is hidden and its data kept: on again,
 * it is all there. The lottery is not among them (it stays, even with events off).
 */
enum ProjectFeature: string
{
    case FORUM = 'forum';
    case CHAT = 'chat';
    case GALLERY = 'gallery';
    case FILES = 'files';
    case EVENTS = 'events';
}
