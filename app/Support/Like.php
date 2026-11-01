<?php

namespace App\Support;

/**
 * What someone searches for, as a LIKE pattern: their % and _ are what they typed, no wildcards.
 */
class Like
{
    public static function containing(string $words): string
    {
        return '%' . addcslashes($words, '\\%_') . '%';
    }
}
