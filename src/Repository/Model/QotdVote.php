<?php

namespace App\Repository\Model;

enum QotdVote: string
{
    case Up = 'up';
    case Down = 'down';
    case Null = 'null';

    public function toInt(): int
    {
        return match ($this) {
            self::Up => 1,
            self::Down => -1,
            self::Null => 0,
        };
    }
}
