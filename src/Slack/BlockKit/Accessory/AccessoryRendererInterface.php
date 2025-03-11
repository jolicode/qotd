<?php

namespace App\Slack\BlockKit\Accessory;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag()]
interface AccessoryRendererInterface
{
    public function render(array $accessory): string;
}
