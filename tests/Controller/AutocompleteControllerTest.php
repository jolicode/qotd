<?php

namespace App\Tests\Controller;

use Doctrine\DBAL\Connection;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AutocompleteControllerTest extends WebTestCase
{
    public function testAuthors(): void
    {
        $client = self::createClientAndLogin();

        $client->request('GET', '/autocomplete/qotd_author', ['query' => 'OLD@EXAMPLE']);

        self::assertResponseIsSuccessful();
        self::assertSame(
            ['results' => [['value' => 'old@example.com', 'text' => 'old@example.com']], 'next_page' => null],
            json_decode((string) $client->getResponse()->getContent(), true),
        );
    }

    public function testAuthorsArePaginated(): void
    {
        $client = self::createClientAndLogin();

        $client->request('GET', '/autocomplete/qotd_author', ['query' => '@']);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(10, $data['results']);
        self::assertStringContainsString('page=2', $data['next_page']);
    }

    public function testFavoriteAuthorsComeFirst(): void
    {
        $client = self::createClientAndLogin();
        $connection = self::getContainer()->get(Connection::class);
        $vote = "UPDATE qotd SET voter_ids = :voterIds WHERE username = 'rich-text@example.com'";

        $client->request('GET', '/autocomplete/qotd_author');
        $authors = array_column(json_decode((string) $client->getResponse()->getContent(), true)['results'], 'value');
        self::assertNotSame('rich-text@example.com', $authors[0]);

        try {
            $connection->executeStatement($vote, ['voterIds' => '{"test-user":"up"}']);

            $client->request('GET', '/autocomplete/qotd_author');

            $authors = array_column(json_decode((string) $client->getResponse()->getContent(), true)['results'], 'value');
            self::assertSame('rich-text@example.com', $authors[0]);
        } finally {
            $connection->executeStatement($vote, ['voterIds' => null]);
        }
    }

    private function createClientAndLogin(): KernelBrowser
    {
        $client = self::createClient();

        $client->loginUser(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']));

        return $client;
    }
}
