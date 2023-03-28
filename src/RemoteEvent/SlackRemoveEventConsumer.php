<?php

namespace App\RemoteEvent;

use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer(name: 'slack')]
class SlackRemoveEventConsumer
{
    public function consume(RemoteEvent $event): void
    {
        dump(__CLASS__, $event);
    }
}
