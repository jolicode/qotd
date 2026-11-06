<?php

namespace App\MessageHandler;

use App\Message\QotdElected;
use App\Qotd\LiveUpdates;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Notifier\Bridge\Mercure\MercureOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Twig\Environment;

#[AsMessageHandler]
final readonly class QotdElectedHandler
{
    public function __construct(
        private HubInterface $hub,
        private ChatterInterface $chatter,
        private Environment $twig,
    ) {
    }

    public function __invoke(QotdElected $message): void
    {
        $this->hub->publish(new Update(
            LiveUpdates::LATEST_TOPIC,
            $this->twig->load('live_updates/streams.html.twig')->renderBlock('elected'),
            private: true,
        ));

        $this->chatter->send(
            (new ChatMessage('A new QOTD has been elected!', new MercureOptions([LiveUpdates::NOTIFICATIONS_TOPIC])))->transport('mercure'),
        );
    }
}
