<?php

namespace App\Twig\Extension;

use Symfony\Component\Intl\Transliterator\EmojiTransliterator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    private static EmojiTransliterator $emojiTransliterator;

    public function getFilters(): array
    {
        return [
            new TwigFilter('replace_emoji', $this->replaceEmoji(...)),
            new TwigFilter('replace_username', $this->replaceUsername(...)),
        ];
    }

    public function replaceEmoji(string $string): string
    {
        $tr = static::$emojiTransliterator ??= EmojiTransliterator::create("emoji-slack", EmojiTransliterator::REVERSE);

        return $tr->transliterate($string);
    }

    public function replaceUsername(string $string): string
    {
        return preg_replace('{<@([A-Z0-9]+)\|(\w+)>}', '@$2', $string);
    }
}
