<?php

namespace App\Tests\Twig\Components;

use App\Repository\QotdRepository;
use App\Twig\Components\QotdCard;
use Doctrine\DBAL\Connection;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class NotVotedCounterTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    public function testRefreshesWhenAQotdIsVoted(): void
    {
        $counter = $this
            ->createLiveComponent('NotVotedCounter')
            ->actingAs(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']))
        ;
        $count = (int) $counter->render()->crawler()->filter('[data-test=not-voted-count]')->text();

        $qotd = self::getContainer()->get(QotdRepository::class)->findOneBy(['username' => 'old@example.com']);
        $connection = self::getContainer()->get(Connection::class);
        $vote = 'UPDATE qotd SET voter_ids = :voterIds WHERE id = :id';

        try {
            $connection->executeStatement($vote, ['voterIds' => '{"test-user":"up"}', 'id' => $qotd?->id]);

            $counter->emit(QotdCard::VOTED_EVENT);

            self::assertSame($count - 1, (int) $counter->render()->crawler()->filter('[data-test=not-voted-count]')->text());
        } finally {
            $connection->executeStatement($vote, ['voterIds' => null, 'id' => $qotd?->id]);
        }
    }
}
