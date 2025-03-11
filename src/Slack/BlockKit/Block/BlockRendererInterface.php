<?php

namespace App\Slack\BlockKit\Block;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag()]
interface BlockRendererInterface
{
    public function render(array $block): string;
}
