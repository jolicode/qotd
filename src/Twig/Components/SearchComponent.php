<?php

namespace App\Twig\Components;

use App\Qotd\QotdCsvExporter;
use App\Repository\QotdRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponse;
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

    #[LiveAction]
    public function exportCsv(QotdCsvExporter $exporter): LiveResponse
    {
        $rows = $this->qotdRepository->iterateSearch($this->query);

        return LiveResponse::downloadFile(
            static function () use ($exporter, $rows): void {
                $output = fopen('php://output', 'w') ?: throw new \RuntimeException('Unable to open the output stream.');

                $exporter->export($rows, $output);
            },
            'qotd-search.csv',
            'text/csv',
        );
    }

    protected function createPagination(): PaginationBuilder
    {
        return $this->qotdRepository
            ->paginateSearch($this->query)
            ->queryParameters(['query' => $this->query])
        ;
    }
}
