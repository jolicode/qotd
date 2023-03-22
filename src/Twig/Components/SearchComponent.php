<?php

namespace App\Twig\Components;

use App\Repository\QotdRepository;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('search')]
final class SearchComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?string $query = '';

    public function __construct(
        private readonly QotdRepository $qotdRepository,
        private readonly RequestStack $requestStack,
    ) {
        $this->query = $this->requestStack->getCurrentRequest()?->query->get('query', '') ?? '';
    }

    /**
     * @return PaginationInterface<string, Qotd>
     */
    public function getPagination(): PaginationInterface
    {
        return $this->qotdRepository->search($this->query);
    }
}
