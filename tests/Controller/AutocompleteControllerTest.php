<?php

namespace App\Tests\Controller;

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

    private function createClientAndLogin(): KernelBrowser
    {
        $client = self::createClient();

        $client->loginUser(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']));

        return $client;
    }
}
