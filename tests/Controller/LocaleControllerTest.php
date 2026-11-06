<?php

namespace App\Tests\Controller;

use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LocaleControllerTest extends WebTestCase
{
    public function testDefaultLocale(): void
    {
        $client = self::createClientAndLogin();

        $client->request('GET', '/awards');

        self::assertSelectorTextContains('html title', 'Awards');
        self::assertSame('en', $client->getCrawler()->filter('html')->attr('lang'));
    }

    public function testLocaleFromTheBrowser(): void
    {
        $client = self::createClientAndLogin();

        $client->request('GET', '/awards', server: ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9']);

        self::assertSelectorTextContains('html title', 'Trophées');
    }

    public function testSwitchWinsOverTheBrowser(): void
    {
        $client = self::createClientAndLogin();

        $client->request('GET', '/locale/fr', server: ['HTTP_REFERER' => 'http://localhost/awards?foo=bar']);

        self::assertResponseRedirects('/awards?foo=bar');

        $client->request('GET', '/awards', server: ['HTTP_ACCEPT_LANGUAGE' => 'en-US']);

        self::assertSelectorTextContains('html title', 'Trophées');
        self::assertSame('fr', $client->getCrawler()->filter('html')->attr('lang'));
    }

    public function testSwitchStaysOnTheSite(): void
    {
        $client = self::createClientAndLogin();

        $client->request('GET', '/locale/fr', server: ['HTTP_REFERER' => 'https://evil.example.com//evil.example.com/foo']);

        self::assertResponseRedirects('/evil.example.com/foo');
    }

    public function testRefusesAnUnknownLocale(): void
    {
        $client = self::createClientAndLogin();

        $client->request('GET', '/locale/de');

        self::assertResponseStatusCodeSame(404);
    }

    public function testIgnoresAnUnknownLocaleInTheCookie(): void
    {
        $client = self::createClientAndLogin();

        $client->request('GET', '/awards', server: ['HTTP_COOKIE' => 'locale=de']);

        self::assertResponseIsSuccessful();
        self::assertSame('en', $client->getCrawler()->filter('html')->attr('lang'));
    }

    private function createClientAndLogin(): KernelBrowser
    {
        $client = self::createClient();

        $client->loginUser(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']));

        return $client;
    }
}
