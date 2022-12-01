<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Validates the challenge request.
 *
 * @see https://api.slack.com/events/url_verification
 */
class ChallengeSubscriber implements EventSubscriberInterface
{
    public function verifyChallenge(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        try {
            $payload = json_decode($event->getRequest()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return;
        }

        if ('url_verification' === $payload['type']) {
            $event->setResponse(new Response($payload['challenge']));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['verifyChallenge', 1024],
        ];
    }
}
