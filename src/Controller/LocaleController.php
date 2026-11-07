<?php

namespace App\Controller;

use App\EventListener\LocaleListener;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestHeader;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    /**
     * @param list<string> $enabledLocales
     */
    #[Route('/locale/{locale}', name: 'locale_switch', methods: ['GET'])]
    public function switch(
        string $locale,
        #[Autowire('%kernel.enabled_locales%')]
        array $enabledLocales,
        #[MapRequestHeader('referer')]
        string $referer = '/',
    ): Response {
        if (!\in_array($locale, $enabledLocales, true)) {
            throw $this->createNotFoundException(\sprintf('The locale "%s" is not enabled.', $locale));
        }

        // Only the path of the referer: this must not redirect to another site
        $response = $this->redirect('/' . ltrim(Request::create($referer)->getRequestUri(), '/'));
        $response->headers->setCookie(Cookie::create(LocaleListener::COOKIE_NAME, $locale, new \DateTimeImmutable('+1 year')));

        return $response;
    }
}
