<?php

namespace App\Tests\Controller;

use App\KeyboardShortcut\KeyboardShortcut;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class KeyboardShortcutsControllerTest extends WebTestCase
{
    public function test(): void
    {
        $client = self::createClient();
        $client->loginUser(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']));

        $crawler = $client->request('GET', '/keyboard-shortcuts', server: ['HTTP_TURBO_FRAME' => 'modal']);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modal-title', 'Keyboard shortcuts');
        self::assertCount(\count(KeyboardShortcut::cases()), $crawler->filter('[data-test=keyboard-shortcut]'));
    }
}
