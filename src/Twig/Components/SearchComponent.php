<?php

namespace App\Twig\Components;

use App\Repository\QotdRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('search')]
final class SearchComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $query;

    public function __construct(
        private readonly QotdRepository $qotdRepository,
        private readonly RequestStack $requestStack,
    ) {
        $this->query = $this->requestStack->getCurrentRequest()?->query->get('query', '') ?? '';
    }

    /**
     * @return array<Qotd>
     */
    public function getQotds(): array
    {
        return $this->qotdRepository->search($this->query);
    }
}
