<?php

namespace App\Slack\BlockKit;

class UserRegistry
{
    private array $users = [];

    public function extractUsers(array $message): void
    {
        $text = $message['text'] ?? '';

        // Extract all username from theses user ids:
        // aaaa <@UMYK1MQ3E|greg 2> bbbb <@U0FLDV6UW> cccc <@U04CVTCQT45>
        $pattern = '/<@([A-Z0-9]+)\|([a-zA-Z0-9\s]+)>/';
        preg_match_all($pattern, $text, $matches);
        foreach ($matches[1] as $index => $userId) {
            $this->users[$userId] = $matches[2][$index];
        }
    }

    public function getUserById(string $userId): string
    {
        return $this->users[$userId] ?? $userId;
    }
}
