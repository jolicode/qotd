<?php

namespace App\Slack\BlockKit\Block\Section;

use App\EmojiTransliterator;
use App\Slack\BlockKit\Block\BlockRendererInterface;

final readonly class MrkdwnRenderer implements BlockRendererInterface
{
    public function __construct(
        private EmojiTransliterator $emojiTransliterator,
    ) {
    }

    public function render(array $block): string
    {
        /**
         * Example:
         * This is a mrkdwn section block :ghost: *this is bold*, and ~this is crossed out~, and <https://google.com|this is a link>
         */
        // We don't need to escape the text here, because it's already escaped by the Slack API
        $text = $block['text']['text'];

        $text = $this->emojiTransliterator->replaceEmoji($text);

        // Convert <https://google.com|this is a link> to <a href="https://google.com">this is a link</a>
        $text = preg_replace('/<([^|]+)\|([^>]+)>/', '<a href="$1">$2</a>', $text);
        // Convert *this is bold* to <b>this is bold</b>
        $text = preg_replace('/\*(.*?)\*/', '<b>$1</b>', $text);
        // Convert ~this is crossed out~ to <s>this is crossed out</s>
        $text = preg_replace('/~(.*?)~/', '<s>$1</s>', $text);
        // Convert `code` to <code>code</code>
        $text = preg_replace('/`(.*?)`/', '<code>$1</code>', $text);

        return str_replace("\n", "<br>\n", $text);
    }
}
