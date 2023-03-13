<?php

namespace App\Entity;

use App\Repository\Model\QotdVote;
use App\Repository\QotdRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: QotdRepository::class)]
class Qotd
{
    #[Groups(['qotd:read'])]
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    public readonly string $id;

    #[Groups(['qotd:read'])]
    #[ORM\Column(type: Types::INTEGER)]
    public int $vote = 0;

    /**
     * @var string[]
     */
    #[ORM\Column(type: Types::JSON)]
    public array $voterIds = [];

    public function __construct(
        #[Groups(['qotd:read'])]
        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        public readonly \DateTimeImmutable $date,

        #[Groups(['qotd:read'])]
        #[ORM\Column(length: 255)]
        public readonly string $permalink,

        #[Groups(['qotd:read'])]
        #[ORM\Column(type: Types::TEXT)]
        public readonly string $message,

        #[Groups(['qotd:read'])]
        #[ORM\Column(length: 255)]
        public readonly string $username,
    ) {
        $this->id = uuid_create();
    }

    public function applyVote(QotdVote $vote, UserInterface $user): void
    {
        $this->vote += match ($vote) {
            QotdVote::Up => 1,
            QotdVote::Down => -1,
        };
        $this->voterIds[] = $user->getUserIdentifier();
    }

    public function hasVoted(UserInterface $user): bool
    {
        return \in_array($user->getUserIdentifier(), $this->voterIds);
    }
}
