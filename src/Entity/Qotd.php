<?php

namespace App\Entity;

use App\Repository\QotdRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QotdRepository::class)]
class Qotd
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    public readonly string $id;

    public function __construct(
        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        public readonly \DateTimeImmutable $date,

        #[ORM\Column(length: 255)]
        public readonly string $permalink,

        #[ORM\Column(type: Types::TEXT)]
        public readonly string $message,

        #[ORM\Column(length: 255)]
        public readonly string $username,
    ) {
        $this->id = uuid_create();
    }
}
