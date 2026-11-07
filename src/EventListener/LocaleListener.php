<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * The locale chosen by the user wins over the one of the browser.
 */
final readonly class LocaleListener
{
    public const string COOKIE_NAME = 'locale';

    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        #[Autowire('%kernel.enabled_locales%')]
        private array $enabledLocales,
    ) {
    }

    // Before the LocaleListener of the framework, which reads "_locale"
    #[AsEventListener(priority: 20)]
    public function setLocale(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $locale = $request->cookies->getString(self::COOKIE_NAME);

        if (\in_array($locale, $this->enabledLocales, true)) {
            $request->attributes->set('_locale', $locale);
        }
    }
}
