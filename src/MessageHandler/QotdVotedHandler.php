<?php

namespace App\MessageHandler;

use App\Message\QotdVoted;
use App\Qotd\LiveUpdates;
use App\Repository\QotdRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twig\Environment;

#[AsMessageHandler]
final readonly class QotdVotedHandler
{
    public function __construct(
        private QotdRepository $qotdRepository,
        private HubInterface $hub,
        private Environment $twig,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(QotdVoted $message): void
    {
        $qotd = $this->qotdRepository->find($message->qotdId);

        if (!$qotd) {
            // "qotd:run --force" replaces the QOTD of a day
            $this->logger->warning('The voted QOTD does not exist anymore.', ['qotdId' => $message->qotdId]);

            return;
        }

        $this->hub->publish(new Update(
            LiveUpdates::VOTES_TOPIC,
            $this->twig->load('live_updates/streams.html.twig')->renderBlock('vote', ['qotd' => $qotd]),
            private: true,
        ));
    }
}
