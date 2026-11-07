<?php

namespace App\Tests\Twig\Components;

use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

class SearchComponentTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    public function testEmptyQuery(): void
    {
        $crawler = self::createSearch()->render()->crawler();

        self::assertCount(0, $crawler->filter('[data-test=quote]'));
        self::assertStringContainsString('Please, type something to search', $crawler->text());
    }

    public function testQueryMatchesNothing(): void
    {
        $crawler = self::createSearch('nothing-matches-this')->render()->crawler();

        self::assertStringContainsString('This query did not match any QOTD', $crawler->text());
        self::assertCount(0, $crawler->filter('[data-live-action-param="exportCsv"]'));
    }

    public function testNextPageShowsOtherQotd(): void
    {
        $search = self::createSearch('est');
        $firstPageIds = self::getQuoteIds($search->render()->crawler());

        $search->call('nextPage');

        self::assertCount(15, $firstPageIds);
        self::assertSame([], array_intersect($firstPageIds, self::getQuoteIds($search->render()->crawler())));
    }

    public function testChangingTheQueryGoesBackToTheFirstPage(): void
    {
        $search = self::createSearch('est');
        $search->call('nextPage');

        $search->set('query', 'et');

        self::assertSame(1, $search->component()->page);
    }

    public function testExportCsv(): void
    {
        $search = self::createSearch('Hello');

        $search->call('exportCsv');

        $response = $search->response();
        self::assertSame('qotd-search.csv', $response->headers->get('X-Live-Download-Filename'));
        self::assertSame('text/csv', $response->headers->get('X-Live-Download-Type'));
    }

    /**
     * @return list<string>
     */
    private static function getQuoteIds(Crawler $crawler): array
    {
        return $crawler->filter('[data-test=quote]')->each(static fn (Crawler $quote): string => (string) $quote->attr('id'));
    }

    private function createSearch(string $query = ''): TestLiveComponent
    {
        return $this
            ->createLiveComponent('search', ['query' => $query, 'paginationRoute' => 'qotd_search'])
            ->actingAs(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']))
        ;
    }
}
