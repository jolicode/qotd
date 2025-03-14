<?php

namespace App\Slack\BlockKit;

use App\Slack\BlockKit\Block\Renderer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

final readonly class MessageRenderer
{
    public function __construct(
        private UserRegistry $userRegistry,
        private Renderer $blockRenderer,
        #[Autowire('%kernel.debug%')]
        private bool $debug,
        private Filesystem $fs,
        #[Autowire('%kernel.project_dir%/var')]
        private string $varDirectory,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function render(array $message): ?string
    {
        $this->userRegistry->extractUsers($message);

        try {
            return $this->blockRenderer->render($message['blocks'] ?? []);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('Cannot render blocks.', [
                'exception' => $e,
                'permalink' => $message['permalink'] ?? null,
                'blocks' => $message['blocks'] ?? [],
            ]);
            $this->fs->dumpFile(
                \sprintf('%s/error-block-render-%s.json', $this->varDirectory, date('Y-m-d-h-i-s')),
                json_encode($message, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES)
            );
            if ($this->debug) {
                throw $e;
            }
        }

        return null;
    }
}
