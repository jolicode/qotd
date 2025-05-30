<?php

namespace App\Slack\BlockKit\Accessory;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class NotSupportedRenderer implements AccessoryRendererInterface
{
    public function __construct(
        #[Autowire('%kernel.debug%')]
        private bool $debug,
    ) {
    }

    public function render(array $accessory): string
    {
        if ($this->debug) {
            return \sprintf('(Accessory type "%s" is not supported, data: %s)', $accessory['type'], json_encode($accessory));
        }

        return '';
    }
}
