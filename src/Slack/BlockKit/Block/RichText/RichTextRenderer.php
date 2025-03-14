<?php

namespace App\Slack\BlockKit\Block\RichText;

use App\EmojiTransliterator;
use App\Slack\BlockKit\Block\BlockRendererInterface;
use App\Slack\BlockKit\Block\Section\PlainTextRenderer;
use App\Slack\BlockKit\UserRegistry;

final readonly class RichTextRenderer implements BlockRendererInterface
{
    public function __construct(
        private PlainTextRenderer $plainTextRenderer,
        private UserRegistry $userRegistry,
        private EmojiTransliterator $emojiTransliterator,
    ) {
    }

    public function render(array $block): string
    {
        $output = '';

        foreach ($block['elements'] as $element) {
            $output .= $this->renderElement($element);
        }

        return $output;
    }

    private function renderElement(array $element): string
    {
        return match ($element['type']) {
            'rich_text_section' => $this->renderRichTextSection($element),
            'rich_text_list' => $this->renderRichTextList($element),
            'rich_text_preformatted' => $this->renderRichTextPreformatted($element),
            'rich_text_quote' => $this->renderRichTextQuote($element),
            default => $element['text'] ?? '',
        };
    }

    private function renderRichTextSection(array $element): string
    {
        $output = '';
        foreach ($element['elements'] as $subElement) {
            $tmp = match ($subElement['type']) {
                'text' => $this->plainTextRenderer->renderText($subElement['text']),
                'link' => '<a href="' . $subElement['url'] . '">' . $this->plainTextRenderer->renderText($subElement['text']) . '</a>',
                'emoji' => $this->emojiTransliterator->replaceEmoji(':' . $subElement['name'] . ':'),
                'user' => '@' . $this->userRegistry->getUserById($subElement['user_id']),
                default => throw new \InvalidArgumentException(\sprintf('Rich text element type "%s" is not supported', $subElement['type'])),
            };

            foreach ($subElement['style'] ?? [] as $style => $true) {
                if (!$true) {
                    continue;
                }

                $tag = match ($style) {
                    'bold' => 'b',
                    'italic' => 'i',
                    'strike' => 's',
                    default => throw new \InvalidArgumentException(\sprintf('Rich text element style "%s" is not supported', $style)),
                };

                $tmp = '<' . $tag . '>' . $tmp . '</' . $tag . '>';
            }

            $output .= $tmp;
        }

        return $output;
    }

    private function renderRichTextPreformatted(array $element): string
    {
        $output = '';
        foreach ($element['elements'] as $subElement) {
            $output .= '<pre>' . $this->plainTextRenderer->renderText($subElement['text']) . '</pre>';
        }

        return $output;
    }

    private function renderRichTextQuote(array $element): string
    {
        $output = '';
        foreach ($element['elements'] as $subElement) {
            $output .= '<blockquote>' . $this->plainTextRenderer->renderText($subElement['text']) . '</blockquote>';
        }

        return $output;
    }

    private function renderRichTextList(array $element): string
    {
        $output = '<ul>';
        $output .= "\n";
        foreach ($element['elements'] as $k => $listItem) {
            if (0 !== $k) {
                $output .= "\n";
            }
            $output .= '<li>' . $this->renderElement($listItem) . '</li>';
        }
        $output .= "\n";
        $output .= '</ul>';

        return $output;
    }
}
