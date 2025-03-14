<?php

namespace App\Slack\BlockKit\Block\Context;

use App\Slack\BlockKit\Block\BlockRendererInterface;
use App\Slack\BlockKit\Block\Image\ImageRenderer;
use App\Slack\BlockKit\Block\Section\MrkdwnRenderer;
use App\Slack\BlockKit\Block\Section\PlainTextRenderer;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

final readonly class ContextRenderer implements BlockRendererInterface
{
    public function __construct(
        #[AutowireLocator(BlockRendererInterface::class)]
        private ContainerInterface $blockRenderers,
    ) {
    }

    public function render(array $block): string
    {
        $output = '';
        foreach ($block['elements'] as $k => $element) {
            if (0 !== $k) {
                $output .= ' ';
            }

            $output .= match ($element['type']) {
                'plain_text' => $this->blockRenderers->get(PlainTextRenderer::class)->render(['text' => $element]),
                'mrkdwn' => $this->blockRenderers->get(MrkdwnRenderer::class)->render(['text' => $element]),
                'image' => $this->blockRenderers->get(ImageRenderer::class)->render($element),
                default => throw new \InvalidArgumentException(\sprintf('Element type "%s" is not supported.', $element['type'])),
            };
        }

        return $output;
    }
}
