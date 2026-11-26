<?php

namespace App\Tests\Security;

use App\Security\GoogleAuthenticator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

class GoogleAuthenticatorTest extends TestCase
{
    public function testItAcceptsAWorkspaceAccountOfAnAllowedDomain(): void
    {
        $user = $this->authenticate(['sub' => '123', 'email' => 'jane@jolicode.com', 'hd' => 'jolicode.com']);

        self::assertSame('jane@jolicode.com', $user->getUserIdentifier());
    }

    public function testItRefusesAPersonalAccountUsingAnEmailOfAnAllowedDomain(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->authenticate(['sub' => '123', 'email' => 'jane+alias@jolicode.com']);
    }

    public function testItRefusesAWorkspaceAccountOfAnotherDomain(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->authenticate(['sub' => '123', 'email' => 'jane@example.org', 'hd' => 'example.org']);
    }

    /**
     * @param array<string, string> $googleResponse
     */
    private function authenticate(array $googleResponse): UserInterface
    {
        $accessToken = new AccessToken(['access_token' => 'foobar']);

        $client = self::createStub(OAuth2ClientInterface::class);
        $client->method('getAccessToken')->willReturn($accessToken);
        $client->method('fetchUserFromToken')->willReturn(new GoogleUser($googleResponse));

        $clientRegistry = self::createStub(ClientRegistry::class);
        $clientRegistry->method('getClient')->willReturn($client);

        $authenticator = new GoogleAuthenticator(self::createStub(UrlGeneratorInterface::class), $clientRegistry, ['jolicode.com']);

        return $authenticator->authenticate(new Request())->getUser();
    }
}
