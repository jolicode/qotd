<?php

namespace App\Repository;

use App\Entity\MyTop;
use App\Entity\Qotd;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @extends ServiceEntityRepository<MyTop>
 */
class MyTopRepository extends ServiceEntityRepository implements ResetInterface
{
    /**
     * Each QOTD card reads the top of the user: without it, a user without top
     * would cost one query per card.
     *
     * @var array<string, MyTop>
     */
    private array $myTops = [];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MyTop::class);
    }

    public function getForUser(UserInterface $user): MyTop
    {
        $userIdentifier = $user->getUserIdentifier();

        return $this->myTops[$userIdentifier] ??= $this->find($userIdentifier) ?? new MyTop($userIdentifier);
    }

    /**
     * @return list<Qotd>
     */
    public function findQotds(MyTop $myTop): array
    {
        $qotds = $this->getEntityManager()->getRepository(Qotd::class)->findBy(['id' => $myTop->getQotdIds()]);
        $positions = array_flip($myTop->getQotdIds());

        usort($qotds, static fn (Qotd $a, Qotd $b): int => $positions[$a->id] <=> $positions[$b->id]);

        return $qotds;
    }

    public function reset(): void
    {
        $this->myTops = [];
    }
}
