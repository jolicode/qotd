<?php

namespace App\Tests\Controller;

use App\Repository\QotdRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
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

    public function testIndexFilteredByAuthor(): void
    {
        $client = self::createClientAndLogin();

        $crawler = $client->request('GET', '/', ['authors' => ['old@example.com']]);

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('[data-test=quote]'));
        self::assertSame('old@example.com', $crawler->filter('select[name="authors[]"] option[selected]')->text());
    }

    public function testIndexFilteredBySeveralAuthors(): void
    {
        $client = self::createClientAndLogin();

        $crawler = $client->request('GET', '/', ['authors' => ['old@example.com', 'rich-text@example.com']]);

        self::assertResponseIsSuccessful();
        self::assertCount(2, $crawler->filter('[data-test=quote]'));
        self::assertCount(2, $crawler->filter('select[name="authors[]"] option'));
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
        self::assertCount(7, $crawler->filter('[data-controller~=not-voted-list] [data-not-voted-list-target=card]'));
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

    public function testNotVotedYetNextPageIsStableWhileVoting(): void
    {
        $client = self::createClientAndLogin();

        $crawler = $client->request('GET', '/not-voted');
        $firstPageIds = self::getQuoteIds($crawler);
        $nextPageLink = $crawler->filter('a[rel=next]')->link();
        $nextPageIds = self::getQuoteIds($client->click($nextPageLink));

        try {
            self::setVoterIds($firstPageIds, '{"test-user":"up"}');

            self::assertSame($nextPageIds, self::getQuoteIds($client->click($nextPageLink)));
        } finally {
            self::setVoterIds($firstPageIds, null);
        }
    }

    public function testNotVotedYetRedirectsToTheFirstPageWhenThePreviousOneIsEmpty(): void
    {
        $client = self::createClientAndLogin();

        $crawler = $client->request('GET', '/not-voted');
        $firstPageIds = self::getQuoteIds($crawler);
        $crawler = $client->click($crawler->filter('a[rel=next]')->link());
        $nextPageIds = self::getQuoteIds($crawler);
        $previousPageLink = $crawler->filter('a[rel=prev]')->link();

        try {
            self::setVoterIds($firstPageIds, '{"test-user":"up"}');

            $client->click($previousPageLink);

            self::assertResponseRedirects('/not-voted');
            self::assertSame($nextPageIds, self::getQuoteIds($client->followRedirect()));
        } finally {
            self::setVoterIds($firstPageIds, null);
        }
    }

    public function testShowDetails(): void
    {
        $client = self::createClientAndLogin();
        $dbQuote = self::getContainer()->get(QotdRepository::class)->findOneBy(['username' => 'rich-text@example.com']);

        $crawler = $client->request('GET', "/qotd/{$dbQuote->id}/details");

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'QOTD of May 12, 2023');
        self::assertSame($dbQuote->message, $crawler->filter('[data-test=raw-data]')->first()->text(normalizeWhitespace: false));
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

    /**
     * @dataProvider provideMenuHighlightsTheGroupOfTheCurrentPageCases
     */
    public function testMenuHighlightsTheGroupOfTheCurrentPage(string $url, string $group): void
    {
        $client = self::createClientAndLogin();

        $client->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, '.navbar-nav .dropdown-item.active');
        self::assertSelectorTextContains('.navbar-nav .dropdown-toggle.active', $group);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideMenuHighlightsTheGroupOfTheCurrentPageCases(): iterable
    {
        yield ['/top', 'Rankings'];
        yield ['/flop', 'Rankings'];
        yield ['/my-top', 'Rankings'];
        yield ['/hall-of-fame', 'Records'];
        yield ['/awards', 'Records'];
        yield ['/stats', 'Records'];
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

    /**
     * @param list<string> $ids
     */
    private static function setVoterIds(array $ids, ?string $voterIds): void
    {
        self::getContainer()->get(Connection::class)->executeStatement(
            'UPDATE qotd SET voter_ids = :voterIds WHERE id IN (:ids)',
            ['voterIds' => $voterIds, 'ids' => $ids],
            ['ids' => ArrayParameterType::STRING],
        );
    }

    private function createClientAndLogin(): KernelBrowser
    {
        $client = self::createClient();

        $client->loginUser(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']));

        return $client;
    }
}
