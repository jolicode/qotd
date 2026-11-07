<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Validates the challenge request.
 *
 * @see https://api.slack.com/events/url_verification
 */
final readonly class ChallengeListener
{
    #[AsEventListener(priority: 1024)]
    public function verifyChallenge(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        try {
            $payload = json_decode($event->getRequest()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return;
        }

        if ('url_verification' === $payload['type']) {
            $event->setResponse(new Response($payload['challenge']));
        }
    }
}
