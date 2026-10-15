<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Logs everyone in as "yolo", so the application can be used in dev without Google OAuth.
 *
 * It replaces the GoogleAuthenticator in the "dev" environment only.
 */
#[When(env: 'dev')]
#[AsDecorator(GoogleAuthenticator::class)]
class YoloAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function supports(Request $request): ?bool
    {
        return null;
    }

    public function authenticate(Request $request): Passport
    {
        // Loaded by the firewall's user provider, like the users authenticated with Google
        return new SelfValidatingPassport(new UserBadge('yolo'));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }

    /**
     * The firewall still uses the GoogleAuthenticator as entry point, which this class decorates.
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        throw new \LogicException('The YoloAuthenticator authenticates every request, so it never has to start an authentication.');
    }
}
