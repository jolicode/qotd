<?php

namespace App\Command;

use App\Slack\BlockKit\MessageRenderer;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'qotd:debug',
    description: 'Debug a JSON file',
)]
final readonly class DebugCommand
{
    public function __construct(
        private MessageRenderer $renderer,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[Argument('The file to read')]
        string $file,
    ): int {
        $message = json_decode(file_get_contents($file), true);

        $renderer = $this->renderer->render($message);
        if (!$renderer) {
            return Command::FAILURE;
        }

        $output->writeln($renderer);

        return Command::SUCCESS;
    }
}
