<?php

namespace App\DataFixtures;

use App\Entity\Qotd;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $qotd = new Qotd(
            new \DateTimeImmutable(),
            'https://example.com',
            <<<'EOTXT'
                Hello

                :speak_no_evil: :+1:

                <https://joli-mapstr.vercel.app/> <https://joli-mapstr.vercel.app/>

                <@UFV8NDFRS|Marion> <@UFV8NDFRS|Marion>

                _foobar_

                ~foobar~

                * list 1
                * list 2
                * list 3

                > quote

                end
            EOTXT,
            'rich-text@example.com',
        );
        $manager->persist($qotd);

        for ($i = 1; $i < 1000; ++$i) {
            $qotd = new Qotd(
                new \DateTimeImmutable("now -{$i} days"),
                'https://example.com',
                "Message {$i}",
                "user-{$i}@example.com",
            );
            $qotd->vote = random_int(0, 100);
            $manager->persist($qotd);
        }

        // Add a gap between the first and the seconde QOTD
        // to test the SQL queries about the stats
        $qotd = new Qotd(
            new \DateTimeImmutable('now -1500 days'),
            'https://example.com',
            "Message {$i}",
            "user-{$i}@example.com",
        );
        $qotd->vote = random_int(0, 100);
        $manager->persist($qotd);

        $manager->flush();
    }
}
