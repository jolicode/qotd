<?php

namespace App\RemoteEvent;

use Symfony\Component\RemoteEvent\RemoteEvent;

class SlackReactionEvent extends RemoteEvent
{
    private string $user;
    private string $reaction;
    private int $timestamp;

    public function getUser(): string
    {
        return $this->user;
    }

    public function setUser(string $user): void
    {
        $this->user = $user;
    }

    public function getReaction(): string
    {
        return $this->reaction;
    }

    public function setReaction(string $reaction): void
    {
        $this->reaction = $reaction;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
}
