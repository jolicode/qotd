<?php

namespace App\Tests\Twig\Components;

use App\Entity\Qotd;
use App\Repository\Model\QotdDirection;
use App\Repository\QotdRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

class QotdCardTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    public function testVote(): void
    {
        $qotd = self::getQotd();
        $card = $this
            ->createLiveComponent('QotdCard', ['qotd' => $qotd])
            ->actingAs(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']))
        ;

        try {
            self::assertSame("qotd-{$qotd->id}", $card->render()->crawler()->filter('[data-test=quote]')->attr('id'));
            self::assertSame('0', self::getVote($card));

            $card->call('vote', ['vote' => 'up']);

            self::assertSame('1', self::getVote($card));
            self::assertFalse($card->response()->headers->has('X-Live-Remove'));

            $card->call('resetVote');

            self::assertSame('0', self::getVote($card));
        } finally {
            self::resetVotes($qotd->id);
        }
    }

    public function testVoteRemovesTheCardFromNotVotedYet(): void
    {
        $qotd = self::getQotd();
        $card = $this
            ->createLiveComponent('QotdCard', ['qotd' => $qotd, 'direction' => QotdDirection::NotVoted])
            ->actingAs(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']))
        ;

        try {
            $card->call('vote', ['vote' => 'down']);

            self::assertSame('1', $card->response()->headers->get('X-Live-Remove'));
            self::assertSame(-1, self::getQotd()->vote);
        } finally {
            self::resetVotes($qotd->id);
        }
    }

    private static function getQotd(): Qotd
    {
        $qotd = self::getContainer()->get(QotdRepository::class)->findOneBy(['username' => 'old@example.com']);
        self::assertInstanceOf(Qotd::class, $qotd);

        return $qotd;
    }

    private static function getVote(TestLiveComponent $card): string
    {
        return $card->render()->crawler()->filter('.qotd-vote')->text();
    }

    private static function resetVotes(string $id): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $qotd = self::getContainer()->get(QotdRepository::class)->find($id);
        self::assertInstanceOf(Qotd::class, $qotd);
        $qotd->vote = 0;
        $qotd->voterIds = null;
        $em->flush();
    }
}
