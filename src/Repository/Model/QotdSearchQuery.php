<?php

namespace App\Repository\Model;

final readonly class QotdSearchQuery
{
    public function __construct(
        public string $query,
        public string $whereSql,
        public string $orderBySql,
    ) {
    }
}
