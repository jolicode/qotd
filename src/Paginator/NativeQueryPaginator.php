<?php

namespace App\Paginator;

use App\Paginator\Mode\NativeQuery;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NativeQueryPaginator implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function items(ItemsEvent $event): void
    {
        if (!$event->target instanceof NativeQuery) {
            return;
        }

        $query = $event->target;

        $count = $this
            ->em
            ->getConnection()
            ->executeQuery('SELECT count(1) FROM ( ' . $query->sql . ' ) AS count_table', $query->parameters)
            ->fetchOne()
        ;

        $params = $query->parameters;
        $params[] = $event->getLimit();
        $params[] = $event->getOffset();
        $qotds = $this
            ->em
            ->createNativeQuery('SELECT * FROM ( ' . $query->sql . ' ) AS q LIMIT ? OFFSET ?', $query->rsm)
            ->setParameters($params)
            ->getResult()
        ;

        $event->count = $count;
        $event->items = $qotds;

        $event->stopPropagation();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items'],
        ];
    }
}
