<?php

namespace App\Slack\BlockKit\Block;

class Escaper
{
    public function escape(string $text): string
    {
        return htmlspecialchars($text, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}
