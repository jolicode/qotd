<?php

namespace App\Slack\BlockKit\Block;

use App\Slack\BlockKit\Accessory\Renderer as AccessoryRenderer;
use App\Slack\BlockKit\Block\Context\ContextRenderer;
use App\Slack\BlockKit\Block\Divider\DividerRenderer;
use App\Slack\BlockKit\Block\Header\HeaderRenderer;
use App\Slack\BlockKit\Block\Image\ImageRenderer;
use App\Slack\BlockKit\Block\RichText\RichTextRenderer;
use App\Slack\BlockKit\Block\Section\FieldsRenderer;
use App\Slack\BlockKit\Block\Section\MrkdwnRenderer;
use App\Slack\BlockKit\Block\Section\PlainTextRenderer;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

final readonly class Renderer
{
    public function __construct(
        #[AutowireLocator(BlockRendererInterface::class)]
        private ContainerInterface $blockRenderers,
        private AccessoryRenderer $accessoryRenderer,
    ) {
    }

    public function render(array $blocks): string
    {
        $output = '';

        foreach ($blocks as $k => $block) {
            if (!\is_array($block)) {
                throw new \InvalidArgumentException(\sprintf('Block at index %d is not an array. Value: %s.', $k, json_encode($block)));
            }

            if (0 !== $k) {
                $output .= "<br />\n";
            }

            $rendererName = $this->getRendererName($block);
            $output .= $this->blockRenderers->get($rendererName)->render($block);

            if (\array_key_exists('accessory', $block)) {
                $accessory = $this->accessoryRenderer->render($block['accessory']);
                if ($accessory) {
                    $output .= " {$accessory}";
                }
            }
        }

        return $output;
    }

    private function getRendererName(array $block): string
    {
        return match ($block['type']) {
            'actions' => NotSupportedRenderer::class,
            'context' => ContextRenderer::class,
            'header' => HeaderRenderer::class,
            'divider' => DividerRenderer::class,
            'image' => ImageRenderer::class,
            'input' => NotSupportedRenderer::class,
            'rich_text' => RichTextRenderer::class,
            'section' => $this->getRendererNameForSection($block),
            default => throw new \InvalidArgumentException(\sprintf('Block type "%s" is not supported', $block['type'])),
        };
    }

    private function getRendererNameForSection(array $block): string
    {
        return match ($block['text']['type'] ?? 'fields') {
            'fields' => FieldsRenderer::class,
            'mrkdwn' => MrkdwnRenderer::class,
            'plain_text' => PlainTextRenderer::class,
            default => throw new \InvalidArgumentException(\sprintf('Section text type "%s" is not supported', $block['text']['type'] ?? 'null')),
        };
    }
}
