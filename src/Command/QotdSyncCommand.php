<?php

namespace App\Command;

use App\Qotd\QotdSynchronizer;
use App\Repository\QotdRepository;
use App\Slack\MessageFetcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'qotd:sync',
    description: 'Resyncs the qotds from slack to the DB',
)]
final readonly class QotdSyncCommand
{
    public function __construct(
        private QotdRepository $qotdRepository,
        private QotdSynchronizer $synchronizer,
        private MessageFetcher $messageFetcher,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option]
        bool $dryRun = false,
    ): int {
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
