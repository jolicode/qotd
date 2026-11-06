<?php

namespace App\Twig\Components;

use App\Entity\Qotd;
use App\Repository\MyTopRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class MyTopList
{
    use DefaultActionTrait;

    public function __construct(
        private readonly MyTopRepository $myTopRepository,
        private readonly Security $security,
    ) {
    }

    /**
     * @return list<Qotd>
     */
    public function getQotds(): array
    {
        $user = $this->security->getUser() ?? throw new \LogicException('The top requires a user.');

        return $this->myTopRepository->findQotds($this->myTopRepository->getForUser($user));
    }

    /**
     * Re-renders the component, nothing else to do.
     */
    #[LiveListener(QotdCard::MY_TOP_CHANGED_EVENT)]
    public function onMyTopChanged(): void
    {
    }
}
