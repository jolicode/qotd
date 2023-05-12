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

                    Message on line 1;
                    Message on line 2;
                    Message on line 3;
                    Message on line 4;

                    :speak_no_evil: :+1:

                    <https://joli-mapstr.vercel.app/> <https://joli-mapstr.vercel.app/>

                    <@UFV8NDFRS|Marion> <@UFV8NDFRS|Marion>

                    _foobar_

                    ~foobar~

                    * list 1
                    * list 2
                    * list 3

                    > quote


                    <script>alert('foo')</script>

                    ```
                    some code
                    ```

                    end
                EOTXT,
            'rich-text@example.com',
        );
        $manager->persist($qotd);

        $faker = \Faker\Factory::create();
        $faker->seed(42);

        for ($i = 1; $i < 1000; ++$i) {
            $qotd = new Qotd(
                new \DateTimeImmutable("now -{$i} days"),
                $faker->url(),
                $faker->paragraph(3),
                $faker->email(),
            );
            $qotd->vote = $faker->numberBetween(0, 100);
            $manager->persist($qotd);
        }

        // Add a gap between the first and the seconde QOTD
        // to test the SQL queries about the stats
        $qotd = new Qotd(
            new \DateTimeImmutable('now -1500 days'),
            'https://example.com',
            'This is a very old QOTD',
            'old@example.com',
        );
        $manager->persist($qotd);

        $manager->flush();
    }
}
