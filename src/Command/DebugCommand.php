<?php

namespace App\Command;

use App\Slack\BlockKit\MessageRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'qotd:debug',
    description: 'Debug a JSON file',
)]
class DebugCommand extends Command
{
    public function __construct(
        private MessageRenderer $renderer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'The file to read')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $message = json_decode(file_get_contents($input->getArgument('file')), true);

        $output->writeln($this->renderer->render($message));

        return Command::SUCCESS;
    }
}
