<?php

namespace App\Tests\Controller;

use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SearchControllerTest extends WebTestCase
{
    public function test(): void
    {
        $client = self::createClientAndLogin();

        $client->request('GET', '/search');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'Search a QOTD');
    }

    private function createClientAndLogin(): KernelBrowser
    {
        $client = self::createClient();

        $client->loginUser(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']));

        return $client;
    }
}
