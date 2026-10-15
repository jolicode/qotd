<?php

namespace App\Tests\Controller;

use App\Repository\QotdRepository;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

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
        self::assertCount(7, $quotes);
        self::assertSame("Hello Message on line 1; Message on line 2; Message on line 3; Message on line 4; 🙊 👍 https://joli-mapstr.vercel.app/ https://joli-mapstr.vercel.app/ @Marion @Marion foobar foobar list 1 list 2 list 3 quote <script>alert('foo')</script> some code end 2023-05-12 by rich-text@example.com 🔗 view in Slack 👎 0 👍", $quotes->first()->text());
    }

    public function testTop(): void
    {
        $client = self::createClientAndLogin();

        $crawler = $client->request('GET', '/top');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'Top QOTD');
        $quotes = $crawler->filter('[data-test=quote]');
        self::assertCount(7, $quotes);
        self::assertSame(100, (int) $quotes->first()->filter('[data-test=quote-vote]')->text());
    }

    public function testFlop(): void
    {
        $client = self::createClientAndLogin();

        $crawler = $client->request('GET', '/flop');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'Worst QOTD');
        $quotes = $crawler->filter('[data-test=quote]');
        self::assertCount(7, $quotes);
        self::assertSame(0, (int) $quotes->first()->filter('[data-test=quote-vote]')->text());
    }

    public function testNotVotedYet(): void
    {
        $client = self::createClientAndLogin();

        $crawler = $client->request('GET', '/not-voted');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'QOTD not voted yet');
        self::assertCount(7, $crawler->filter('[data-test=quote]'));
    }

    public function testNotVotedYetNextPage(): void
    {
        $client = self::createClientAndLogin();

        $crawler = $client->request('GET', '/not-voted');
        $firstPageIds = self::getQuoteIds($crawler);

        $crawler = $client->click($crawler->filter('a[rel=next]')->link());

        self::assertResponseIsSuccessful();
        $nextPageIds = self::getQuoteIds($crawler);
        self::assertCount(7, $nextPageIds);
        self::assertSame([], array_intersect($firstPageIds, $nextPageIds));
    }

    public function testShow(): void
    {
        $client = self::createClientAndLogin();
        $dbQuote = self::getContainer()->get(QotdRepository::class)->findOneBy(['username' => 'old@example.com']);

        $crawler = $client->request('GET', "/qotd/{$dbQuote->id}");

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', "QOTD #{$dbQuote->id}");
        self::assertSame("qotd-{$dbQuote->id}", $crawler->filter('[data-test=quote]')->attr('id'));
        self::assertSame(0, (int) $crawler->filter('[data-test=quote-vote]')->text());
    }

    public static function assertTestSelectorTextContains(string $selector, string $text, string $message = ''): void
    {
        self::assertSelectorTextContains(\sprintf('[data-test=%s]', $selector), $text, $message);
    }

    /**
     * @return list<string>
     */
    private static function getQuoteIds(Crawler $crawler): array
    {
        return $crawler
            ->filter('[data-test=quote]')
            ->each(static fn (Crawler $quote): string => str_replace('qotd-', '', (string) $quote->attr('id')))
        ;
    }

    private function createClientAndLogin(): KernelBrowser
    {
        $client = self::createClient();

        $client->loginUser(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']));

        return $client;
    }
}
