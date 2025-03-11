<?php

namespace App\Repository;

use App\Entity\Qotd;
use App\Form\Model\QotdFilters;
use App\Paginator\Mode\NativeQuery;
use App\Repository\Model\QotdDirection;
use App\Repository\Model\QotdVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
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
    private const int MAX_PER_PAGE = 7;

    public function __construct(
        ManagerRegistry $registry,
        private readonly PaginatorInterface $paginator,
    ) {
        parent::__construct($registry, Qotd::class);
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
    public function findForHomepage(int $page, QotdDirection $direction, ?QotdFilters $filters = null): PaginationInterface
    {
        $qb = $this->createQueryBuilder('q');

        if ($filters) {
            if ($filters->author) {
                $qb->andWhere('q.username = :author')->setParameter('author', $filters->author);
            }
            match ($filters->withImage) {
                true => $qb->andWhere('q.images != \'[]\''),
                false => $qb->andWhere('q.images = \'[]\''),
                null => null,
            };
            match ($filters->withVideo) {
                true => $qb->andWhere('q.videos != \'[]\''),
                false => $qb->andWhere('q.videos = \'[]\''),
                null => null,
            };
        }

        match ($direction) {
            QotdDirection::Top => $qb->addOrderBy('q.vote', 'DESC')->addOrderBy('q.date', 'DESC'),
            QotdDirection::Flop => $qb->addOrderBy('q.vote', 'ASC')->addOrderBy('q.date', 'DESC'),
            QotdDirection::Latest => $qb->addOrderBy('q.date', 'DESC')->addOrderBy('q.vote', 'DESC'),
            default => throw new \InvalidArgumentException('Invalid direction'),
        };

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            self::MAX_PER_PAGE,
        );
    }

    /**
     * @return PaginationInterface<string, Qotd>
     */
    public function findForHomepageNotVoted(int $page, UserInterface $user): PaginationInterface
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
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
            self::MAX_PER_PAGE,
        );
    }

    public function search(string $query): array
    {
        if (!$query) {
            return [];
        }

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Qotd::class, 'q');

        $select = $rsm->generateSelectClause();

        $sql = <<<EOSQL
                SELECT {$select}
                FROM qotd AS q
                WHERE message_ts @@ websearch_to_tsquery(:query)
                ORDER BY ts_rank(message_ts, websearch_to_tsquery(:query)) DESC
                LIMIT 15
            EOSQL;

        $results = $this
            ->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->execute([
                'query' => $query,
            ])
        ;

        if ($results) {
            return $results;
        }

        $sql = <<<EOSQL
                SELECT {$select}, word_similarity(message, :query) AS sml
                FROM qotd AS q
                WHERE q.message ~~* :query2
                ORDER BY sml DESC, date DESC
                LIMIT 15
            EOSQL;

        return $this
            ->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->execute([
                'query' => $query,
                'query2' => "%{$query}%",
            ])
        ;
    }

    public function countMostQuotedUsers(): array
    {
        $sql = <<<'EOSQL'
                WITH
                    most_quoted_users AS (
                        SELECT username, COUNT(username) AS count
                        FROM qotd
                        GROUP BY username
                        ORDER BY count DESC, username ASC
                        LIMIT 9
                    ),
                    others AS (
                        SELECT 'other' AS username, COUNT(id) AS count
                        FROM qotd q
                            LEFT JOIN most_quoted_users m ON m.username = q.username
                        WHERE m.username IS NULL
                    )
                SELECT *
                FROM most_quoted_users
                UNION
                    SELECT *
                    FROM others
                ORDER BY count DESC, username ASC
            EOSQL;

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addScalarResult('username', 'username', 'string');
        $rsm->addScalarResult('count', 'count', 'integer');

        return $this
            ->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->getResult()
        ;
    }

    public function countMostUpVotedUsers(): array
    {
        $sql = <<<'EOSQL'
                WITH
                    most_up_voted_users AS (
                        SELECT username, SUM(vote) AS vote
                        FROM qotd
                        GROUP BY username
                        ORDER BY vote DESC, username ASC
                        LIMIT 9
                    ),
                    others AS (
                        SELECT 'other' AS username, SUM(q.vote) AS vote
                        FROM qotd q
                            LEFT JOIN most_up_voted_users m ON m.username = q.username
                        WHERE m.username IS NULL
                    )
                SELECT *
                FROM most_up_voted_users
                UNION
                    SELECT *
                    FROM others
                ORDER BY vote DESC, username ASC
            EOSQL;

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addScalarResult('username', 'username', 'string');
        $rsm->addScalarResult('vote', 'vote', 'integer');

        return $this
            ->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->getResult()
        ;
    }

    public function countBiggestVotingUsers(): array
    {
        $sql = <<<'EOSQL'
                WITH
                    biggest_voter_users AS (
                        SELECT
                            jsonb_object_keys(voter_ids) as voter_username,
                            count(username) as vote
                        FROM qotd
                        WHERE voter_ids IS NOT NULL
                        GROUP BY voter_username
                        ORDER BY vote DESC, voter_username ASC
                        LIMIT 9
                    ),
                    others AS (
                        SELECT 'other' AS voter_username, SUM(q.vote) AS vote
                        FROM qotd q
                            LEFT JOIN biggest_voter_users b ON b.voter_username = q.username
                        WHERE b.voter_username IS NULL
                    )
                SELECT *
                FROM biggest_voter_users
                UNION
                    SELECT *
                    FROM others
                ORDER BY vote DESC, voter_username ASC
            EOSQL;

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addScalarResult('voter_username', 'username', 'string');
        $rsm->addScalarResult('vote', 'vote', 'integer');

        return $this
            ->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->getResult()
        ;
    }

    public function countOver(string $period): array
    {
        $sql = <<<'EOSQL'
                SELECT date_trunc(:period, date) AS period, COUNT(id) AS count, SUM(vote) AS vote
                FROM qotd
                GROUP BY period
                ORDER BY period
                limit 100
            EOSQL;

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addScalarResult('period', 'period', 'datetime_immutable');
        $rsm->addScalarResult('count', 'count', 'integer');
        $rsm->addScalarResult('vote', 'vote', 'integer');

        return $this
            ->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->setParameters([
                'period' => $period,
            ])
            ->getResult()
        ;
    }

    public function computeAwards(): array
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addScalarResult('rank', 'rank', 'integer');
        $rsm->addScalarResult('username', 'username', 'string');
        $rsm->addScalarResult('score', 'score', 'integer');
        $rsm->addScalarResult('awards', 'awards', 'json');

        $sql = <<<'SQL'
                WITH
                    raw_periods AS (
                        select column1 as period, column2 as factor from (values
                            ('day', 1 ^ 2),
                            ('week', 2 ^ 2),
                            ('month', 3 ^ 2),
                            ('year', 4 ^ 2)
                        ) as x
                    ),
                    date_boundary AS (
                        SELECT
                            min(date_trunc(p.period, date)) AS startp,
                            max(date_trunc(p.period, date)) AS endp,
                            p.*
                        FROM qotd
                            FULL OUTER JOIN raw_periods p on true
                        GROUP BY p.period, p.factor
                    ),
                    periods AS (
                        SELECT
                            generate_series(startp, endp, ('1 ' || period)::interval) AS start_of_period,
                            period,
                            factor
                        FROM date_boundary
                    ),
                    qotd AS (
                        SELECT
                            p.*,
                            rank() OVER w AS rank,
                            q.*
                        FROM periods p
                            INNER JOIN qotd q on date_trunc(p.period, q.date) = p.start_of_period
                        WINDOW w AS (
                            PARTITION BY p.period, p.start_of_period
                            ORDER BY q.vote DESC, q.date DESC
                        )
                    ),
                    aggregation AS (
                        SELECT
                            period,
                            username,
                            factor,
                            count(1) as count
                        FROM qotd
                        WHERE rank = 1
                        GROUP BY username, period, factor
                        order by username asc, factor desc
                    ),
                    final AS (
                        SELECT
                            rank() over (order by sum(count * factor) desc, username asc) as rank,
                            username,
                            sum(count * factor) as score,
                            json_object_agg(
                                period, count
                            ) as awards
                        FROM aggregation
                        GROUP BY username
                    )
                SELECT *
                FROM final
                ORDER BY rank ASC
            SQL;

        return $this
            ->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->getResult()
        ;
    }

    public function findBestsOver(string $period): array
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Qotd::class, 'q');
        $rsm->addScalarResult('start_of_period', 'start_of_period', 'datetime_immutable');

        $select = $rsm->generateSelectClause();

        $sql = <<<SQL
                WITH
                    date_boundary AS (
                        SELECT
                            min(date_trunc(:period, date)) AS startp,
                            max(date_trunc(:period, date)) AS endp
                        FROM qotd
                    ),
                    periods AS (
                        SELECT generate_series(startp, endp, ('1 ' || :period)::interval) AS start_of_period
                        FROM date_boundary
                    ),
                    qotd AS (
                        SELECT
                            p.start_of_period,
                            rank() OVER w AS rank,
                            q.*
                        FROM periods p
                            LEFT OUTER JOIN qotd q on date_trunc(:period, q.date) = p.start_of_period
                        WINDOW w AS (
                            PARTITION BY p.start_of_period
                            ORDER BY q.vote DESC, q.date DESC
                        )
                    )
                SELECT start_of_period, {$select}
                FROM qotd q
                WHERE rank = 1
                ORDER BY start_of_period DESC
                LIMIT 20
            SQL;

        return $this
            ->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->setParameters([
                'period' => $period,
            ])
            ->getResult()
        ;
    }

    public function getAuthors(): array
    {
        return $this->createQueryBuilder('q')
            ->select('q.username', 'count(q.username) as HIDDEN count')
            ->addOrderBy('count', 'DESC')
            ->addOrderBy('q.username', 'ASC')
            ->groupBy('q.username')
            ->getQuery()
            ->getResult(Query::HYDRATE_SCALAR_COLUMN)
        ;
    }

    public function findRandom(): ?Qotd
    {
        // This is not efficient but i don't want to create a custom function for this
        $count = $this->createQueryBuilder('q')
            ->select('count(q.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if (0 === $count) {
            return null;
        }

        $offset = random_int(0, $count - 1);

        return $this->createQueryBuilder('q')
            ->setFirstResult($offset)
            ->setMaxResults(1)
            ->addOrderBy('q.id', 'ASC')
            ->getQuery()
            ->getSingleResult()
        ;
    }
}
