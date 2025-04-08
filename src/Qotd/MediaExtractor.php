<?php

namespace App\Qotd;

use App\Entity\Qotd;
use App\Qotd\Model\Media;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MediaExtractor
{
    public function __construct(
        #[Target('slack.bot.client')]
        private HttpClientInterface $botClient,
        #[Autowire('%env(SLACK_BOT_TOKEN)%')]
        private string $slackBotToken,
        private SluggerInterface $slugger,
        #[Autowire('%upload_dir%')]
        private string $uploadDirectory,
        private Filesystem $fs,
    ) {
    }

    public function extraMedia(Qotd $qotd, array $message): Media
    {
        $images = [];
        $videos = [];

        foreach ($message['files'] ?? [] as $file) {
            $format = explode('/', (string) $file['mimetype'])[0];

            if (!\in_array($format, ['image', 'video'], true)) {
                continue;
            }

            $response = $this->botClient->request('GET', $file['url_private_download'], [
                'auth_bearer' => $this->slackBotToken,
            ]);

            $mediaSuffix = \sprintf('%s---%s.%s', uuid_create(), $this->slugger->slug($file['name']), $file['filetype']);
            $mediaPath = \sprintf('%s/%s---%s', $this->uploadDirectory, $qotd->id, $mediaSuffix);

            $this->fs->dumpFile($mediaPath, $response->getContent());

            if ('image' === $format) {
                $images[] = $mediaSuffix;
            } elseif ('video' === $format) {
                $videos[] = $mediaSuffix;
            }
        }

        return new Media($images, $videos);
    }
}
