<?php

namespace App\DataFixtures;

use App\Entity\Qotd;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 100; ++$i) {
            $qotd = new Qotd(
                new \DateTimeImmutable("now -{$i} days"),
                'https://example.com',
                "Message {$i}",
                "user-{$i}@example.com",
            );
            $qotd->vote = $i;
            $manager->persist($qotd);
        }

        $manager->flush();
    }
}
