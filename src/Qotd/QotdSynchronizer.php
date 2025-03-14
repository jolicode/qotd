<?php

namespace App\Qotd;

use App\Entity\Qotd;
use App\Slack\BlockKit\MessageRenderer;
use Doctrine\ORM\EntityManagerInterface;

final readonly class QotdSynchronizer
{
    public function __construct(
        private MediaExtractor $mediaExtractor,
        private MessageRenderer $messageRendered,
        private EntityManagerInterface $em,
    ) {
    }

    public function sync(Qotd $qotd, array $message, bool $dryRun = false): void
    {
        $media = $this->mediaExtractor->extraMedia($qotd, $message);
        $qotd->images = $media->images;
        $qotd->videos = $media->videos;

        $qotd->messageRendered = $this->messageRendered->render($message);

        if ($dryRun) {
            return;
        }

        $this->em->persist($qotd);
        $this->em->flush();
    }
}
