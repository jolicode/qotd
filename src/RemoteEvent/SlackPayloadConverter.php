<?php

namespace App\RemoteEvent;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RemoteEvent\Exception\ParseException;
use Symfony\Component\RemoteEvent\PayloadConverterInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

final class SlackPayloadConverter implements PayloadConverterInterface
{
    public function __construct(
        #[Autowire('%env(SLACK_REACTION_TO_SEARCH)%')]
        private readonly string $reactionToSearch,
    ) {
    }

    public function convert(array $payload): RemoteEvent
    {
        if ($payload['event']['reaction'] !== $this->reactionToSearch) {
            throw new ParseException(sprintf('Unsupported reaction "%s".', $payload['event']['reaction']));
        }

        $event = new SlackReactionEvent($payload['event']['type'], $payload['event_id'], $payload);
        $event->setReaction($payload['event']['reaction']);
        $event->setUser($payload['event']['user']);
        $event->setTimestamp((int) $payload['event']['event_ts']);

        return $event;
    }
}
