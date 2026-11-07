<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final readonly class SignatureListener
{
    public function __construct(
        #[Autowire('%env(SLACK_SIGNING_SECRET)%')]
        private string $signinSecret,
    ) {
    }

    #[AsEventListener(priority: 32 - 1)]
    public function verifySignature(RequestEvent $event): void
    {
        if (!$this->signinSecret) {
            return;
        }

        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->attributes->get('slack', false)) {
            return;
        }

        $body = (string) $request->getContent();
        $signature = $request->headers->get('X-Slack-Signature');
        $timestamp = $request->headers->get('X-Slack-Request-Timestamp');

        $payload = \sprintf('v0:%s:%s', $timestamp, $body);

        $signatureTmp = \sprintf('v0=%s', hash_hmac('sha256', $payload, $this->signinSecret));

        if ($signatureTmp !== $signature) {
            $event->setResponse(new Response('You are not slack', 401));
        }
    }
}
