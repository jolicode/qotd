<?php

namespace App\Slack\BlockKit\Accessory;

use App\Slack\BlockKit\Block\Image\ImageRendererTrait;
use App\Slack\BlockKit\Block\Section\PlainTextRenderer;

final readonly class ImageRenderer implements AccessoryRendererInterface
{
    use ImageRendererTrait;

    public function __construct(
        private PlainTextRenderer $plainTextRenderer,
    ) {
    }
}
