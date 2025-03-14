<?php

namespace App\Slack\BlockKit\Block\Section;

use App\Slack\BlockKit\Block\BlockRendererInterface;
use App\Slack\BlockKit\Block\Escaper;

final readonly class PlainTextRenderer implements BlockRendererInterface
{
    public function __construct(
        private Escaper $escaper,
    ) {
    }

    public function render(array $block): string
    {
        return $this->renderText($block['text']['text']);
    }

    public function renderText(string $text): string
    {
        return $this->escaper->escape($text);
    }
}
