<?php

namespace App\Tests\Entity;

use App\Entity\MyTop;
use App\Entity\Qotd;
use App\Qotd\Exception\MyTopFullException;
use PHPUnit\Framework\TestCase;

class MyTopTest extends TestCase
{
    public function testToggle(): void
    {
        $myTop = new MyTop('user');
        $qotd = self::createQotd();

        $myTop->toggle($qotd);
        self::assertTrue($myTop->has($qotd));

        $myTop->toggle($qotd);
        self::assertFalse($myTop->has($qotd));
        self::assertSame([], $myTop->getQotdIds());
    }

    public function testRefusesAQotdOverTheMaximum(): void
    {
        $myTop = new MyTop('user');
        for ($i = 0; $i < MyTop::MAX_SIZE; ++$i) {
            $myTop->toggle(self::createQotd());
        }

        self::assertTrue($myTop->isFull());

        $this->expectException(MyTopFullException::class);

        $myTop->toggle(self::createQotd());
    }

    public function testAQotdOfAFullTopCanStillBeRemoved(): void
    {
        $myTop = new MyTop('user');
        $qotd = self::createQotd();
        $myTop->toggle($qotd);
        for ($i = 1; $i < MyTop::MAX_SIZE; ++$i) {
            $myTop->toggle(self::createQotd());
        }

        $myTop->toggle($qotd);

        self::assertFalse($myTop->isFull());
    }

    public function testMove(): void
    {
        $myTop = new MyTop('user');
        [$first, $second, $third] = [self::createQotd(), self::createQotd(), self::createQotd()];
        foreach ([$first, $second, $third] as $qotd) {
            $myTop->toggle($qotd);
        }

        $myTop->move($third, 1);
        self::assertSame([$third->id, $first->id, $second->id], $myTop->getQotdIds());

        $myTop->move($third, 99);
        self::assertSame([$first->id, $second->id, $third->id], $myTop->getQotdIds());
    }

    public function testMoveIgnoresAQotdOutOfTheTop(): void
    {
        $myTop = new MyTop('user');
        $qotd = self::createQotd();
        $myTop->toggle($qotd);

        $myTop->move(self::createQotd(), 1);

        self::assertSame([$qotd->id], $myTop->getQotdIds());
    }

    private static function createQotd(): Qotd
    {
        return new Qotd(new \DateTimeImmutable(), 'https://example.com', 'Hello', 'foo@example.com');
    }
}
