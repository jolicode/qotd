<?php

namespace App\Stats;

enum StatsPeriod: string
{
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';

    public function getLabel(): string
    {
        return match ($this) {
            self::Week => 'Weekly',
            self::Month => 'Monthly',
            self::Year => 'Yearly',
        };
    }
}
