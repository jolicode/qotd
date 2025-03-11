<?php

namespace App\Slack\BlockKit\Block\Divider;

use App\Slack\BlockKit\Block\BlockRendererInterface;

class DividerRenderer implements BlockRendererInterface
{
    public function render(array $block): string
    {
        return '<hr>';
    }
}
