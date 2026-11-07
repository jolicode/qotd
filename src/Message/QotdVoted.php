<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final readonly class QotdVoted
{
    public function __construct(
        public string $qotdId,
    ) {
    }
}
