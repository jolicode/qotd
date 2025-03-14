<?php

namespace App\Command;

use App\Qotd\QotdCreator;
use App\Repository\QotdRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'qotd:run',
    description: 'Search and Update Quotes of the Day',
)]
class QotdRunCommand extends Command
{
    public function __construct(
        #[Target('slack.bot.client')]
        private readonly HttpClientInterface $botClient,
        #[Target('slack.user.client')]
        private readonly HttpClientInterface $userClient,
        #[Autowire('%env(SLACK_CHANNEL_ID_FOR_SUMMARY)%')]
        private readonly string $channelIdForSummary,
        #[Autowire('%env(SLACK_REACTION_TO_SEARCH)%')]
        private readonly string $reactionToSearch,
        private readonly QotdRepository $qotdRepository,
        private readonly QotdCreator $qotdCreator,
        private readonly UrlGeneratorInterface $router,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('date', InputArgument::OPTIONAL, 'date', 'yesterday')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not update the database, and do not post.')
            ->addOption('force', null, InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        $date = new \DateTimeImmutable($input->getArgument('date'));
        $lowerDate = $date->setTime(0, 0, 0);
        $upperDate = $date->setTime(23, 59, 59);

        $io->comment(\sprintf('Looking between %s and %s', $lowerDate->format('Y-m-d H:i:s'), $upperDate->format('Y-m-d H:i:s')));

        $previousQotd = null;
        $previousQotd = $this->qotdRepository->findOneBy(['date' => $date]);
        if (!$dryRun && !$input->getOption('force') && $previousQotd) {
            $io->error(\sprintf('Qotd for %s already exists', $date->format('Y-m-d')));

            return Command::FAILURE;
        }

        $messages = $this->userClient->request('GET', 'search.messages', [
            'query' => [
                'query' => "has::{$this->reactionToSearch}:",
                'count' => 100,
                'sort' => 'timestamp',
            ],
        ])->toArray()['messages']['matches'] ?? throw new \RuntimeException('Cannot get messages.');

        $bestMessage = null;
        $bestScore = 0;

        foreach ($messages as $message) {
            if (!$this->canUseMessage($message, $lowerDate, $upperDate)) {
                continue;
            }

            try {
                $reactions = $this->botClient->request('GET', 'reactions.get', [
                    'query' => [
                        'channel' => $message['channel']['id'],
                        'timestamp' => $message['ts'],
                    ],
                ])->toArray()['message']['reactions'] ?? []; // (race condition: reaction could have been removed)
            } catch (HttpExceptionInterface $e) {
                $this->logger->error('Cannot get reactions.', [
                    'channel' => $message['channel']['id'],
                    'timestamp' => $message['ts'],
                    'exception' => $e,
                ]);

                continue;
            }

            foreach ($reactions as $reaction) {
                if ($reaction['name'] !== $this->reactionToSearch) {
                    continue;
                }
                if ($reaction['count'] > $bestScore) {
                    $bestScore = $reaction['count'];
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
            if ($previousQotd) {
                $this->em->remove($previousQotd);
            }

            $this->qotdCreator->createQotd($bestMessage, $date);

            $this->botClient->request('POST', 'chat.postMessage', [
                'json' => [
                    'channel' => $this->channelIdForSummary,
                    'text' => \sprintf(
                        "%s's QOTD was: %s\nYou can vote for it on %s",
                        ucfirst((string) $input->getArgument('date')),
                        $bestMessage['permalink'],
                        $this->router->generate('qotd_index_not_voted', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    ),
                ],
            ]);
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

        return !$channel['is_private'];
    }
}
