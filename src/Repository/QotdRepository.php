<?php

namespace App\Repository;

use App\Entity\Qotd;
use App\Paginator\Mode\NativeQuery;
use App\Repository\Model\QotdDirection;
use App\Repository\Model\QotdVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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

    /**
     * @return PaginationInterface<string, Qotd>
     */
    public function findForHomepageNotVoted(int $page, UserInterface $user): PaginationInterface
    {
        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata(Qotd::class, 'q');

        $select = $rsm->generateSelectClause();

        $query = new NativeQuery(
            "SELECT {$select} FROM qotd AS q WHERE coalesce(voter_ids->>:userId, :notVoted) = :notVoted",
            [
                'userId' => $user->getUserIdentifier(),
                'notVoted' => QotdVote::Null->value,
            ],
            $rsm,
        );

        return $this->paginator->paginate(
            $query,
            $page,
            20,
        );
    }

    /**
     * @return PaginationInterface<string, Qotd>
     */
    public function search(string $query): PaginationInterface
    {
        $query = $this
            ->createQueryBuilder('q')
            ->where('q.message LIKE :query')->setParameter('query', "%{$query}%")
            ->addOrderBy('q.vote', 'DESC')
            ->addOrderBy('q.date', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
        ;

        return $this->paginator->paginate(
            $query,
            1,
            20,
        );
    }

    // Not used yet
    // public function countOver(string $period)
    // {
    //     $sql = <<<'EOSQL'
    //         SELECT date_trunc('week', date) AS period, COUNT(id) as count
    //         FROM qotd
    //         GROUP BY period
    //         ORDER BY period
    //     EOSQL;
    // }

    public function findBestsOver(string $period)
    {
        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata(Qotd::class, 'q');
        $rsm->addScalarResult('start_of_period', 'start_of_period', 'datetime_immutable');

        $select = $rsm->generateSelectClause();

        $sql = <<<EOSQL
            WITH
                date_boundary AS (
                    SELECT
                        min(date_trunc(:period, date)) AS startw,
                        max(date_trunc(:period, date)) AS endw
                    FROM qotd
                ),
                periods AS (
                    SELECT generate_series(startw, endw, ('1 ' || :period)::interval) AS start_of_period
                    FROM date_boundary
                ),
                qotd AS (
                    SELECT
                        p.start_of_period,
                        q.*,
                        rank() OVER w AS rank
                    FROM periods p
                        LEFT OUTER JOIN qotd q on date_trunc(:period, q.date) = p.start_of_period
                    WINDOW w AS (
                        PARTITION BY p.start_of_period ORDER BY q.vote DESC, q.date DESC
                    )
                )
            SELECT start_of_period, {$select}
            FROM qotd q
            WHERE rank = 1
            ORDER BY start_of_period DESC
        EOSQL;

        return $this
            ->_em
            ->createNativeQuery($sql, $rsm)
            ->setParameters([
                'period' => $period,
            ])
            ->getResult()
        ;
    }
}
