<?php

namespace App\Paginator\Mode;

use Doctrine\ORM\Query\ResultSetMapping;

readonly class NativeQuery
{
    public function __construct(
        public string $sql,
        public array $parameters,
        public ResultSetMapping $rsm,
    ) {
    }
}
