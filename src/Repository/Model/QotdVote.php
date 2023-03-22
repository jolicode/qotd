<?php

namespace App\Repository\Model;

enum QotdVote: string
{
    case Up = 'up';
    case Down = 'down';
    case Null = 'null';
    // Deal with legacy
    case Unknown = 'unknown';

    public function toInt(): int
    {
        return match ($this) {
            self::Up => 1,
            self::Down => -1,
            self::Null, self::Unknown => 0,
        };
    }
}
