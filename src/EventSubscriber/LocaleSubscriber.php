<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The locale chosen by the user wins over the one of the browser.
 */
final readonly class LocaleSubscriber implements EventSubscriberInterface
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

    public static function getSubscribedEvents(): array
    {
        return [
            // Before the LocaleListener of the framework, which reads "_locale"
            KernelEvents::REQUEST => ['setLocale', 20],
        ];
    }

    public function setLocale(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $locale = $request->cookies->getString(self::COOKIE_NAME);

        if (\in_array($locale, $this->enabledLocales, true)) {
            $request->attributes->set('_locale', $locale);
        }
    }
}
