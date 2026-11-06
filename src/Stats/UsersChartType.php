<?php

namespace App\Stats;

enum UsersChartType: string
{
    case MostQuoted = 'most-quoted';
    case MostUpVoted = 'most-up-voted';
    case BiggestVoting = 'biggest-voting';

    public function getTitle(): string
    {
        return match ($this) {
            self::MostQuoted => 'Most Quoted Users',
            self::MostUpVoted => 'Most Up-Voted Users',
            self::BiggestVoting => 'Biggest Voting Users',
        };
    }

    /**
     * The biggest voting users are voters: they may have no QOTD to list.
     */
    public function hasAuthorLabels(): bool
    {
        return self::BiggestVoting !== $this;
    }
}
