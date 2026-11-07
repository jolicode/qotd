<?php

namespace App\Tests\Controller;

use App\Entity\MyTop;
use App\Entity\Qotd;
use App\Repository\QotdRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyTopControllerTest extends WebTestCase
{
    public function testEmptyTop(): void
    {
        $client = self::createClientAndLogin();

        $client->request('GET', '/my-top');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'My top');
        self::assertAnySelectorTextContains('.card-body', 'Your top is empty');
    }

    public function testListsTheTopInOrder(): void
    {
        $client = self::createClientAndLogin();
        [$first, $second] = self::getQotds();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $myTop = new MyTop('test-user');
        $myTop->toggle($second);
        $myTop->toggle($first);
        $em->persist($myTop);
        $em->flush();

        try {
            $crawler = $client->request('GET', '/my-top');

            self::assertResponseIsSuccessful();
            self::assertSame("qotd-{$second->id}", $crawler->filter('[data-test=quote]')->first()->attr('id'));
            self::assertSame("qotd-{$first->id}", $crawler->filter('[data-test=quote]')->last()->attr('id'));
        } finally {
            self::removeMyTop();
        }
    }

    public function testMoveRefusesAnInvalidToken(): void
    {
        $client = self::createClientAndLogin();
        [$first, $second] = self::getQotds();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $myTop = new MyTop('test-user');
        $myTop->toggle($first);
        $myTop->toggle($second);
        $em->persist($myTop);
        $em->flush();

        try {
            $client->request('POST', "/my-top/{$second->id}/position", ['position' => 1, '_token' => 'invalid']);

            self::assertResponseRedirects();
            $em->clear();
            self::assertSame([$first->id, $second->id], $em->find(MyTop::class, 'test-user')?->getQotdIds());
        } finally {
            self::removeMyTop();
        }
    }

    public function testMoveChangesThePosition(): void
    {
        $client = self::createClientAndLogin();
        [$first, $second] = self::getQotds();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $myTop = new MyTop('test-user');
        $myTop->toggle($first);
        $myTop->toggle($second);
        $em->persist($myTop);
        $em->flush();

        try {
            $client->request('POST', "/my-top/{$second->id}/position", ['position' => 1, '_token' => self::getCsrfToken($client)]);

            self::assertResponseStatusCodeSame(204);
            $em->clear();
            self::assertSame([$second->id, $first->id], $em->find(MyTop::class, 'test-user')?->getQotdIds());
        } finally {
            self::removeMyTop();
        }
    }

    /**
     * @dataProvider provideMoveRefusesAnInvalidPositionCases
     */
    public function testMoveRefusesAnInvalidPosition(string $position): void
    {
        $client = self::createClientAndLogin();
        [$first, $second] = self::getQotds();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $myTop = new MyTop('test-user');
        $myTop->toggle($first);
        $myTop->toggle($second);
        $em->persist($myTop);
        $em->flush();

        try {
            $client->request('POST', "/my-top/{$second->id}/position", ['position' => $position, '_token' => self::getCsrfToken($client)]);

            self::assertResponseStatusCodeSame(400);
            $em->clear();
            self::assertSame([$first->id, $second->id], $em->find(MyTop::class, 'test-user')?->getQotdIds());
        } finally {
            self::removeMyTop();
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideMoveRefusesAnInvalidPositionCases(): iterable
    {
        yield 'zero' => ['0'];
        yield 'negative' => ['-1'];
        yield 'not a number' => ['not-a-number'];
    }

    private static function getCsrfToken(KernelBrowser $client): string
    {
        $crawler = $client->request('GET', '/my-top');

        return (string) $crawler->filter('[data-ui--sortable-csrf-token-value]')->attr('data-ui--sortable-csrf-token-value');
    }

    /**
     * @return list<Qotd>
     */
    private static function getQotds(): array
    {
        return self::getContainer()->get(QotdRepository::class)->findBy([], limit: 2);
    }

    private static function removeMyTop(): void
    {
        self::getContainer()->get(EntityManagerInterface::class)->getConnection()->executeStatement('DELETE FROM my_top');
    }

    private function createClientAndLogin(): KernelBrowser
    {
        $client = self::createClient();

        $client->loginUser(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']));

        return $client;
    }
}
