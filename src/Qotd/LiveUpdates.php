<?php

namespace App\Qotd;

use App\Entity\Qotd;
use App\Message\QotdElected;
use App\Message\QotdVoted;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Publishes what happens to every open page, through a worker and a Mercure
 * hub. When disabled, the app works the same, without any of them.
 */
final readonly class LiveUpdates
{
    public const string LATEST_TOPIC = 'qotd:latest';
    public const string NOTIFICATIONS_TOPIC = 'qotd:notifications';
    public const string VOTES_TOPIC = 'qotd:votes';

    public function __construct(
        private MessageBusInterface $bus,
        #[Autowire('%env(bool:LIVE_UPDATES_ENABLED)%')]
        private bool $enabled,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function publishVote(Qotd $qotd): void
    {
        if ($this->isEnabled()) {
            $this->bus->dispatch(new QotdVoted($qotd->id));
        }
    }

    public function publishElection(): void
    {
        if ($this->isEnabled()) {
            $this->bus->dispatch(new QotdElected());
        }
    }
}
