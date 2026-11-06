<?php

namespace App\Message;

final readonly class QotdVoted
{
    public function __construct(
        public string $qotdId,
    ) {
    }
}
