<?php

namespace App\Slack\BlockKit\Block\Image;

use App\Slack\BlockKit\Block\BlockRendererInterface;
use App\Slack\BlockKit\Block\Section\PlainTextRenderer;

class ImageRenderer implements BlockRendererInterface
{
    use ImageRendererTrait;

    public function __construct(
        private PlainTextRenderer $plainTextRenderer,
    ) {
    }
}
