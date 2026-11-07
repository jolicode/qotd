<?php

namespace App\Qotd;

use App\Entity\Qotd;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final readonly class QotdImageUploader
{
    public function __construct(
        private SluggerInterface $slugger,
        #[Autowire('%upload_dir%')]
        private string $uploadDirectory,
    ) {
    }

    public function upload(Qotd $qotd, UploadedFile $image): void
    {
        $imageName = \sprintf(
            '%s---%s.%s',
            uuid_create(),
            $this->slugger->slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)),
            $image->guessExtension(),
        );

        $image->move($this->uploadDirectory, \sprintf('%s---%s', $qotd->id, $imageName));

        $qotd->addImage($imageName);
    }
}
