<?php

namespace App\Slack\BlockKit\Accessory;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

final readonly class Renderer
{
    public function __construct(
        #[AutowireLocator(AccessoryRendererInterface::class)]
        private ContainerInterface $accessoryRenderers,
    ) {
    }

    public function render(array $accessory): string
    {
        $rendererName = $this->getRendererName($accessory);

        return $this->accessoryRenderers->get($rendererName)->render($accessory);
    }

    private function getRendererName(array $accessory): string
    {
        return match ($accessory['type']) {
            'button' => NotSupportedRenderer::class,
            'checkboxes' => NotSupportedRenderer::class,
            'datepicker' => NotSupportedRenderer::class,
            'image' => ImageRenderer::class,
            'conversations_select' => NotSupportedRenderer::class,
            'multi_conversations_select' => NotSupportedRenderer::class,
            'multi_static_select' => NotSupportedRenderer::class,
            'overflow' => NotSupportedRenderer::class,
            'radio_buttons' => NotSupportedRenderer::class,
            'static_select' => NotSupportedRenderer::class,
            'timepicker' => NotSupportedRenderer::class,
            'users_select' => NotSupportedRenderer::class,
            default => throw new \InvalidArgumentException(\sprintf('Accessory type "%s" is not supported.', $accessory['type'])),
        };
    }
}
