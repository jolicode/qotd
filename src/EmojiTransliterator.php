<?php

namespace App;

use Symfony\Component\Emoji\EmojiTransliterator as SymfonyEmojiTransliterator;

final readonly class EmojiTransliterator
{
    private SymfonyEmojiTransliterator $emojiTransliterator;

    public function __construct()
    {
        $this->emojiTransliterator = SymfonyEmojiTransliterator::create('emoji-slack', SymfonyEmojiTransliterator::REVERSE);
    }

    public function replaceEmoji(string $string): string
    {
        return (string) $this->emojiTransliterator->transliterate($string);
    }
}
