<?php

namespace App\Slack\BlockKit;

use App\Slack\BlockKit\Block\Renderer;

final readonly class MessageRenderer
{
    public function __construct(
        private Renderer $blockRenderer,
        private UserRegistry $userRegistry,
    ) {
    }

    public function render(array $message): string
    {
        $this->userRegistry->extractUsers($message);

        return $this->blockRenderer->render($message['blocks']);
    }
}
