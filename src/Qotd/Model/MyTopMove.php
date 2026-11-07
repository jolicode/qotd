<?php

namespace App\Qotd\Model;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class MyTopMove
{
    public function __construct(
        /** @var positive-int */
        #[Assert\Positive]
        public int $position,
    ) {
    }
}
