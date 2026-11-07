<?php

namespace App\Tests\Entity;

use App\Entity\Qotd;
use App\Repository\Model\QotdVote;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use PHPUnit\Framework\TestCase;

class QotdTest extends TestCase
{
    public function testVotesByVoterIgnoresCancelledVotes(): void
    {
        $qotd = new Qotd(new \DateTimeImmutable(), 'https://example.com', 'Hello', 'foo@example.com');
        $alice = new OAuthUser('alice', ['ROLE_USER']);
        $bob = new OAuthUser('bob', ['ROLE_USER']);

        self::assertSame([], $qotd->getVotesByVoter());

        $qotd->applyVote(QotdVote::Up, $alice);
        $qotd->applyVote(QotdVote::Down, $bob);
        $qotd->applyVote(QotdVote::Null, $bob);

        self::assertSame(['alice' => QotdVote::Up], $qotd->getVotesByVoter());
    }
}
