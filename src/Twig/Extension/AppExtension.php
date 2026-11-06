<?php

namespace App\Twig\Extension;

use App\Qotd\LiveUpdates;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Emoji\EmojiTransliterator;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private static EmojiTransliterator $emojiTransliterator;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly LiveUpdates $liveUpdates,
        #[Autowire('%kernel.environment%')]
        private readonly string $env,
    ) {
    }

    public function getFunctions(): iterable
    {
        yield new TwigFunction('tt', $this->whenTest(...));
        yield new TwigFunction('get_turbo_frame', $this->getTurboFrame(...));
        yield new TwigFunction('live_updates_enabled', $this->liveUpdates->isEnabled(...));
    }

    public function getFilters(): iterable
    {
        yield new TwigFilter('replace_emoji', $this->replaceEmoji(...));
        yield new TwigFilter('replace_username', $this->replaceUsername(...));
    }

    public function replaceEmoji(string $string): string
    {
        $tr = self::$emojiTransliterator ??= EmojiTransliterator::create('emoji-slack', EmojiTransliterator::REVERSE);

        return (string) $tr->transliterate($string);
    }

    public function replaceUsername(string $string): string
    {
        return (string) preg_replace('{<@([A-Z0-9]+)\|(\w+)>}', '@$2', $string);
    }

    public function whenTest(string $name): string
    {
        if ('test' !== $this->env) {
            return '';
        }

        return \sprintf('data-test=%s', $name);
    }

    public function getTurboFrame(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request?->headers->get('Turbo-Frame') ?? null;
    }
}
