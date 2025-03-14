<?php

namespace App\Command;

use App\Qotd\QotdSynchronizer;
use App\Repository\QotdRepository;
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
        #[Target('slack.bot.client')]
        private readonly HttpClientInterface $botClient,
        private readonly QotdSynchronizer $synchronizer,
        private readonly LoggerInterface $logger = new NullLogger(),
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
            $message = $this->getSlackMsgByPermalink($permalink);

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

    // Adapted from https://stackoverflow.com/a/61401783/685587
    private function getSlackMsgByPermalink(string $permalink): ?array
    {
        $pathElements = explode('/', substr($permalink, 8));
        $channel = $pathElements[2];

        $url = '';
        if (str_contains($permalink, 'thread_ts')) {
            // Threaded message, use conversations.replies endpoint
            $ts = $pathElements[3];
            $ts = substr($ts, 0, strpos($ts, '?'));
            $ts = substr($ts, 0, \strlen($ts) - 6) . '.' . substr($ts, \strlen($ts) - 6);

            $latest = substr($pathElements[3], strpos($pathElements[3], 'thread_ts=') + 10);
            if (str_contains($latest, '&')) {
                $latest = substr($latest, 0, strpos($latest, '&'));
            }

            $url = 'https://slack.com/api/conversations.replies';
            $query = [
                'channel' => $channel,
                'ts' => $ts,
                'latest' => $latest,
                'inclusive' => 'true',
                'limit' => '1',
            ];
        } else {
            // Non-threaded message, use conversations.history endpoint
            $latest = substr($pathElements[3], 1);
            if (str_contains($latest, '?')) {
                $latest = substr($latest, 0, strpos($latest, '?'));
            }
            $latest = substr($latest, 0, \strlen($latest) - 6) . '.' . substr($latest, \strlen($latest) - 6);

            $url = 'https://slack.com/api/conversations.history';
            $query = [
                'channel' => $channel,
                'latest' => $latest,
                'inclusive' => 'true',
                'limit' => '1',
            ];
        }

        $result = null;

        try {
            $response = $this->botClient->request('GET', $url, [
                'query' => $query,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            $result = $response->toArray();

            if (!$result['ok']) {
                throw new \RuntimeException($result['error']);
            }

            return $result['messages'][0];
        } catch (ExceptionInterface|\RuntimeException $e) {
            $this->logger->error('Cannot get slack message.', [
                'exception' => $e,
                'permalink' => $permalink,
                'result' => $result,
            ]);
        }

        return null;
    }
}
