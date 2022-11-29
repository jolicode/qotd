<?php

namespace App\Repository;

use App\Entity\Qotd;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Qotd::class);
    }

    public function save(Qotd $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Qotd $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

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
}
