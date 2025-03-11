<?php

namespace App\Slack\BlockKit\Block;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class NotSupportedRenderer implements BlockRendererInterface
{
    public function __construct(
        #[Autowire(env: 'bool:APP_DEBUG_NOT_SUPPORTED')]
        private bool $debug,
    ) {
    }

    public function render(array $accessory): string
    {
        if ($this->debug) {
            return \sprintf('(Block type "%s" is not supported, data: %s)', $accessory['type'], json_encode($accessory));
        }

        return '';
    }
}
