<?php

namespace App\Tests\Qotd;

use App\Entity\Qotd;
use App\Message\QotdElected;
use App\Message\QotdVoted;
use App\Qotd\LiveUpdates;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class LiveUpdatesTest extends TestCase
{
    public function testPublishesNothingWhenDisabled(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        $liveUpdates = new LiveUpdates($bus, false);

        self::assertFalse($liveUpdates->isEnabled());
        $liveUpdates->publishVote(self::createQotd());
        $liveUpdates->publishElection();
    }

    public function testPublishesThroughTheBus(): void
    {
        $messages = [];
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(static function (object $message) use (&$messages): Envelope {
            $messages[] = $message;

            return new Envelope($message);
        });
        $qotd = self::createQotd();

        $liveUpdates = new LiveUpdates($bus, true);
        $liveUpdates->publishVote($qotd);
        $liveUpdates->publishElection();

        self::assertCount(2, $messages);
        self::assertInstanceOf(QotdVoted::class, $messages[0]);
        self::assertSame($qotd->id, $messages[0]->qotdId);
        self::assertInstanceOf(QotdElected::class, $messages[1]);
    }

    private static function createQotd(): Qotd
    {
        return new Qotd(new \DateTimeImmutable(), 'https://example.com', 'Hello', 'foo@example.com');
    }
}
