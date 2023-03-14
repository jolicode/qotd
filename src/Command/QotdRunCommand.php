<?php

namespace App\Command;

use App\Entity\Qotd;
use App\Repository\QotdRepository;
use JoliCode\Slack\Client;
use JoliCode\Slack\Exception\SlackErrorResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'qotd:run',
    description: 'Search and Update Quotes of the Day',
)]
class QotdRunCommand extends Command
{
    public function __construct(
        #[Autowire(service: Client::class . '.bot')]
        private readonly Client $botClient,
        #[Autowire(service: Client::class . '.user')]
        private readonly Client $userClient,
        #[Autowire('%env(SLACK_CHANNEL_ID_FOR_SUMMARY)%')]
        private readonly string $channelIdForSummary,
        #[Autowire('%env(SLACK_REACTION_TO_SEARCH)%')]
        private readonly string $reactionToSearch,
        private readonly QotdRepository $qotdRepository,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('date', InputArgument::OPTIONAL, 'date', 'yesterday')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not update the database, and do not post.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        $date = new \DateTimeImmutable($input->getArgument('date'));
        $lowerDate = $date->setTime(0, 0, 0);
        $upperDate = $date->setTime(23, 59, 59);

        $io->comment(sprintf('Looking between %s and %s', $lowerDate->format('Y-m-d H:i:s'), $upperDate->format('Y-m-d H:i:s')));

        if (!$dryRun) {
            $qotd = $this->qotdRepository->findOneBy(['date' => $date]);
            if ($qotd) {
                $io->error(sprintf('Qotd for %s already exists', $date->format('Y-m-d')));

                return Command::FAILURE;
            }
        }

        $messages = $this->userClient->searchMessages([
            'query' => "has::{$this->reactionToSearch}:",
            'count' => 100,
            'sort' => 'timestamp',
        ])['messages']['matches'];

        $bestMessage = null;
        $bestScore = 0;

        foreach ($messages as $message) {
            if (!$this->canUseMessage($message, $lowerDate, $upperDate)) {
                continue;
            }

            try {
                $reactions = $this->botClient->reactionsGet([
                    'channel' => $message['channel']['id'],
                    'timestamp' => $message['ts'],
                ])->message->reactions;
            } catch (SlackErrorResponse $e) {
                $this->logger->error('Cannot get reactions.', [
                    'channel' => $message['channel']['id'],
                    'timestamp' => $message['ts'],
                    'exception' => $e,
                ]);

                continue;
            }

            foreach ($reactions as $reaction) {
                if ($reaction->name !== $this->reactionToSearch) {
                    continue;
                }
                if ($reaction->count > $bestScore) {
                    $bestScore = $reaction->count;
                    $bestMessage = $message;
                }
            }
        }

        if (!$bestMessage) {
            $io->comment('No QOTD found.');

            return Command::SUCCESS;
        }

        $io->comment('Best message: ' . $bestMessage['permalink']);

        if (!$dryRun) {
            $this->botClient->chatPostMessage([
                'channel' => $this->channelIdForSummary,
                'text' => sprintf('%s\'s QOTD was: %s', ucfirst($input->getArgument('date')), $bestMessage['permalink']),
            ]);

            $this->qotdRepository->save(new Qotd(
                date: $date,
                permalink: $bestMessage['permalink'],
                message: $bestMessage['text'],
                username: $bestMessage['username'],
            ), true);
        }

        return Command::SUCCESS;
    }

    private function canUseMessage(array $message, \DateTimeImmutable $lowerDate, \DateTimeImmutable $upperDate): bool
    {
        $messageCreatedAt = new \DateTimeImmutable('@' . $message['ts']);
        if ($messageCreatedAt < $lowerDate || $messageCreatedAt > $upperDate) {
            return false;
        }

        $channel = $message['channel'];

        if ($channel['is_group']) {
            return false;
        }
        if ($channel['is_im']) {
            return false;
        }
        if ($channel['is_private']) {
            return false;
        }

        return true;
    }
}
