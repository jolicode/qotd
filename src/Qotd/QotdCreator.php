<?php

namespace App\Qotd;

use App\Entity\Qotd;
use Doctrine\ORM\EntityManagerInterface;

final readonly class QotdCreator
{
    public function __construct(
        private QotdSynchronizer $synchronizer,
        private EntityManagerInterface $em,
    ) {
    }

    public function createQotd(array $message, \DateTimeImmutable $date): Qotd
    {
        $qotd = new Qotd(
            date: $date,
            permalink: $message['permalink'],
            message: $message['text'],
            username: $message['username'],
        );

        $this->synchronizer->sync($qotd, $message);

        $this->em->persist($qotd);
        $this->em->flush();

        return $qotd;
    }
}
