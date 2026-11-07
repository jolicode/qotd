<?php

namespace App\Twig\Components;

use App\Repository\QotdRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class NotVotedCounter
{
    use DefaultActionTrait;

    public function __construct(
        private readonly QotdRepository $qotdRepository,
        private readonly Security $security,
    ) {
    }

    public function getCount(): int
    {
        $user = $this->security->getUser() ?? throw new \LogicException('The count of QOTD not voted yet requires a user.');

        return $this->qotdRepository->countNotVoted($user);
    }

    /**
     * Re-renders the component, nothing else to do.
     */
    #[LiveListener(QotdCard::VOTED_EVENT)]
    public function onVoted(): void
    {
    }
}
