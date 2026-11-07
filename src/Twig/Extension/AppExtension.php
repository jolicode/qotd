<?php

namespace App\Twig\Extension;

use App\Qotd\LiveUpdates;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Emoji\EmojiTransliterator;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

final class AppExtension
{
    private static EmojiTransliterator $emojiTransliterator;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly LiveUpdates $liveUpdates,
        #[Autowire('%kernel.environment%')]
        private readonly string $env,
    ) {
    }

    #[AsTwigFilter('replace_emoji')]
    public function replaceEmoji(string $string): string
    {
        $tr = self::$emojiTransliterator ??= EmojiTransliterator::create('emoji-slack', EmojiTransliterator::REVERSE);

        return (string) $tr->transliterate($string);
    }

    #[AsTwigFilter('replace_username')]
    public function replaceUsername(string $string): string
    {
        return (string) preg_replace('{<@([A-Z0-9]+)\|(\w+)>}', '@$2', $string);
    }

    #[AsTwigFunction('tt')]
    public function whenTest(string $name): string
    {
        if ('test' !== $this->env) {
            return '';
        }

        return \sprintf('data-test=%s', $name);
    }

    #[AsTwigFunction('get_turbo_frame')]
    public function getTurboFrame(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request?->headers->get('Turbo-Frame') ?? null;
    }

    #[AsTwigFunction('live_updates_enabled')]
    public function isLiveUpdatesEnabled(): bool
    {
        return $this->liveUpdates->isEnabled();
    }
}
