<?php

namespace App\Repository;

use App\Entity\Qotd;
use App\Repository\Model\QotdDirection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Qotd>
 *
 * @method Qotd|null find($id, $lockMode = null, $lockVersion = null)
 * @method Qotd|null findOneBy(array $criteria, array $orderBy = null)
 * @method Qotd[]    findAll()
 * @method Qotd[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QotdRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly PaginatorInterface $paginator,
    ) {
        parent::__construct($registry, Qotd::class);
    }

    public function save(Qotd $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findLast(): ?Qotd
    {
        return $this->createQueryBuilder('q')
            ->orderBy('q.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return PaginationInterface<string, Qotd>
     */
    public function findForHomepage(int $page, QotdDirection $direction): PaginationInterface
    {
        $qb = $this->createQueryBuilder('q');

        match ($direction) {
            QotdDirection::Top => $qb->addOrderBy('q.vote', 'DESC')->addOrderBy('q.date', 'DESC'),
            QotdDirection::Flop => $qb->addOrderBy('q.vote', 'ASC')->addOrderBy('q.date', 'DESC'),
            QotdDirection::Latest => $qb->addOrderBy('q.date', 'DESC')->addOrderBy('q.vote', 'DESC'),
        };

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            20,
        );
    }
}
