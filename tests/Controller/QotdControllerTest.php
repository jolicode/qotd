<?php

namespace App\Tests\Controller;

use App\Repository\QotdRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class QotdControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = self::createClientAndLogin();

        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertTestSelectorTextContains('brand', 'QOTD');
        self::assertSelectorTextContains('title', 'Latest QOTD');
        $quotes = $crawler->filter('[data-test=quote]');
        self::assertCount(20, $quotes);
        self::assertSame("Hello Message on line 1; Message on line 2; Message on line 3; Message on line 4; 🙊 👍 https://joli-mapstr.vercel.app/ https://joli-mapstr.vercel.app/ @Marion @Marion foobar foobar list 1 list 2 list 3 quote <script>alert('foo')</script> some code end 2023-05-12 by rich-text@example.com 🔗 view in Slack 👎 0 👍", $quotes->first()->text());
    }

    public function testTop(): void
    {
        $client = self::createClientAndLogin();

        $crawler = $client->request('GET', '/top');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'Top QOTD');
        $quotes = $crawler->filter('[data-test=quote]');
        self::assertCount(20, $quotes);
        self::assertSame(100, (int) $quotes->first()->filter('[data-test=quote-vote]')->text());
    }

    public function testFlop(): void
    {
        $client = self::createClientAndLogin();

        $crawler = $client->request('GET', '/flop');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'Worst QOTD');
        $quotes = $crawler->filter('[data-test=quote]');
        self::assertCount(20, $quotes);
        self::assertSame(0, (int) $quotes->first()->filter('[data-test=quote-vote]')->text());
    }

    public function testNotVotedYet(): void
    {
        $client = self::createClientAndLogin();

        $crawler = $client->request('GET', '/not-voted');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'QOTD not voted yet');
        $quotes = $crawler->filter('[data-test=quote]');
        self::assertCount(20, $quotes);

        $firstQuote = $quotes->first();
        $id = str_replace('qotd-default-', '', $firstQuote->attr('id'));

        try {
            $form = $firstQuote->filter('[data-test=vote-up]')->form();
            $client->submit($form);
            self::assertResponseStatusCodeSame(302);

            $dbQuote = self::getContainer()->get(QotdRepository::class)->find($id);
            $this->assertSame(1, $dbQuote->vote);
        } finally {
            $em = self::getContainer()->get(EntityManagerInterface::class);
            $em->clear();
            $dbQuote = self::getContainer()->get(QotdRepository::class)->find($id);
            $dbQuote->vote = 0;
            $dbQuote->voterIds = null;
            $em->flush();
        }
    }

    public function testShowAndVote(): void
    {
        $client = self::createClientAndLogin();
        $dbQuote = self::getContainer()->get(QotdRepository::class)->findOneBy(['username' => 'old@example.com']);

        $crawler = $client->request('GET', "/qotd/{$dbQuote->id}");

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', "QOTD #{$dbQuote->id}");
        self::assertSame(0, (int) $crawler->filter('[data-test=quote]')->first()->filter('[data-test=quote-vote]')->text());

        $form = $crawler
            ->filter('[data-test=quote]')
            ->first()
            ->filter('[data-test=vote-up]')
            ->form()
        ;
        $client->submit($form);

        self::assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', "QOTD #{$dbQuote->id}");
        self::assertSame(1, (int) $crawler->filter('[data-test=quote]')->first()->filter('[data-test=quote-vote]')->text());

        $form = $crawler
            ->filter('[data-test=quote]')
            ->first()
            ->filter('[data-test=vote-up]')
            ->form()
        ;
        $client->submit($form);

        self::assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', "QOTD #{$dbQuote->id}");
        self::assertSame(0, (int) $crawler->filter('[data-test=quote]')->first()->filter('[data-test=quote-vote]')->text());
    }

    public static function assertTestSelectorTextContains(string $selector, string $text, string $message = ''): void
    {
        self::assertSelectorTextContains(sprintf('[data-test=%s]', $selector), $text, $message);
    }

    private function createClientAndLogin(): KernelBrowser
    {
        $client = self::createClient();

        $client->loginUser(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']));

        return $client;
    }
}
