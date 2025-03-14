<?php

namespace App\Slack\BlockKit\Block\Header;

use App\EmojiTransliterator;
use App\Slack\BlockKit\Block\BlockRendererInterface;
use App\Slack\BlockKit\Block\Section\PlainTextRenderer;

final readonly class HeaderRenderer implements BlockRendererInterface
{
    public function __construct(
        private PlainTextRenderer $plainTextRenderer,
        private EmojiTransliterator $emojiTransliterator,
        private string $tag = 'h3',
    ) {
    }

    public function render(array $block): string
    {
        $text = $block['text']['text'];
        $text = $this->plainTextRenderer->renderText($text);
        $text = $this->emojiTransliterator->replaceEmoji($text);

        return \sprintf('<%s>%s</%s>', $this->tag, $text, $this->tag);
    }
}
