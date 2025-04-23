<?php

namespace App\Command;

use App\Qotd\QotdSynchronizer;
use App\Repository\QotdRepository;
use App\Slack\MessageFetcher;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'qotd:sync',
    description: 'Resyncs the qotds from slack to the DB',
)]
class QotdSyncCommand extends Command
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
        private readonly QotdSynchronizer $synchronizer,
        private readonly MessageFetcher $messageFetcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dryRun = $input->getOption('dry-run');

        $qotds = $this->qotdRepository->findAll();
        $io->progressStart(\count($qotds));

        foreach ($qotds as $qotd) {
            $permalink = $qotd->permalink;
            $message = $this->messageFetcher->getMessageByPermalink($permalink);

            if (!$message) {
                continue;
            }

            $this->synchronizer->sync($qotd, $message, $dryRun);

            $io->progressAdvance();
        }

        $io->progressFinish();

        $io->success('Qotds synced successfully.');

        return Command::SUCCESS;
    }
}
