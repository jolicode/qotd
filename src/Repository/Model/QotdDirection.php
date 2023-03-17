<?php

namespace App\Repository\Model;

enum QotdDirection: string
{
    case Latest = 'latest';
    case Top = 'top';
    case Flop = 'flop';
    case NotVoted = 'not-voted';

    public function toTwigActiveSection(): string
    {
        return match ($this) {
            self::Latest => 'qotd_latest',
            self::Top => 'qotd_top',
            self::Flop => 'qotd_flop',
            self::NotVoted => 'qotd_not_voted',
        };
    }
}
