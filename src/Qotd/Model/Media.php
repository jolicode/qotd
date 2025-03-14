<?php

namespace App\Qotd\Model;

final readonly class Media
{
    public function __construct(
        public array $images = [],
        public array $videos = [],
    ) {
    }
}
