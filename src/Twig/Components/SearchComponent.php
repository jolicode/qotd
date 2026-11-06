<?php

namespace App\Twig\Components;

use App\Repository\QotdRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\Pagination\LiveComponent\ComponentWithPaginationTrait;
use Symfony\UX\Pagination\PaginationBuilder;

#[AsLiveComponent('search')]
final class SearchComponent
{
    use ComponentWithPaginationTrait;
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true, onUpdated: 'onQueryUpdated')]
    public string $query = '';

    public function __construct(
        private readonly QotdRepository $qotdRepository,
    ) {
    }

    public function onQueryUpdated(): void
    {
        $this->resetPage();
    }

    protected function createPagination(): PaginationBuilder
    {
        return $this->qotdRepository
            ->paginateSearch($this->query)
            ->queryParameters(['query' => $this->query])
        ;
    }
}
