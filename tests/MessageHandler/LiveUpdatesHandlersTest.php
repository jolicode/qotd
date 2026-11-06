<?php

namespace App\Tests\MessageHandler;

use App\Entity\Qotd;
use App\Message\QotdElected;
use App\Message\QotdVoted;
use App\MessageHandler\QotdElectedHandler;
use App\MessageHandler\QotdVotedHandler;
use App\Qotd\LiveUpdates;
use App\Repository\QotdRepository;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Twig\Environment;

class LiveUpdatesHandlersTest extends KernelTestCase
{
    /** @var list<Update> */
    private array $updates = [];

    public function testPublishesTheVote(): void
    {
        $qotd = self::getContainer()->get(QotdRepository::class)->findOneBy(['username' => 'old@example.com']);
        self::assertInstanceOf(Qotd::class, $qotd);

        $handler = new QotdVotedHandler(
            self::getContainer()->get(QotdRepository::class),
            $this->createHub(),
            self::getContainer()->get(Environment::class),
            new NullLogger(),
        );
        $handler(new QotdVoted($qotd->id));

        self::assertCount(1, $this->updates);
        self::assertSame([LiveUpdates::VOTES_TOPIC], $this->updates[0]->getTopics());
        self::assertTrue($this->updates[0]->isPrivate());
        self::assertStringContainsString("targets=\"#qotd-{$qotd->id} .qotd-vote\"", (string) $this->updates[0]->getData());
    }

    public function testIgnoresAQotdThatNoLongerExists(): void
    {
        $handler = new QotdVotedHandler(
            self::getContainer()->get(QotdRepository::class),
            $this->createHub(),
            self::getContainer()->get(Environment::class),
            new NullLogger(),
        );

        $handler(new QotdVoted(uuid_create()));

        self::assertSame([], $this->updates);
    }

    public function testPublishesTheElection(): void
    {
        $chatter = $this->createMock(ChatterInterface::class);
        $chatter
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(static fn (ChatMessage $message): bool => 'mercure' === $message->getTransport()))
        ;

        $handler = new QotdElectedHandler($this->createHub(), $chatter, self::getContainer()->get(Environment::class));
        $handler(new QotdElected());

        self::assertCount(1, $this->updates);
        self::assertSame([LiveUpdates::LATEST_TOPIC], $this->updates[0]->getTopics());
        self::assertStringContainsString('target="qotd-latest-banner"', (string) $this->updates[0]->getData());
    }

    private function createHub(): HubInterface
    {
        return new MockHub('https://example.com/.well-known/mercure', new StaticTokenProvider('token'), function (Update $update): string {
            $this->updates[] = $update;

            return 'id';
        });
    }
}
