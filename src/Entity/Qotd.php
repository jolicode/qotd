<?php

namespace App\Entity;

use App\Repository\Model\QotdVote;
use App\Repository\QotdRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QotdRepository::class)]
#[HasLifecycleCallbacks]
class Qotd
{
    #[Groups(['qotd:read'])]
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    public readonly string $id;

    #[Groups(['qotd:read'])]
    #[ORM\Column(type: Types::INTEGER)]
    public int $vote = 0;

    /** @var ?string[] */
    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true], nullable: true)]
    public ?array $voterIds = null;

    /** @var string[] */
    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true])]
    public array $images = [];

    #[Assert\Length(min: 5)]
    #[ORM\Column(type: Types::TEXT)]
    public string $context = '';

    #[ORM\Column()]
    public \DateTimeImmutable $updatedAt;

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
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function applyVote(QotdVote $vote, UserInterface $user): void
    {
        if (null === $this->voterIds) {
            $this->voterIds = [];
        }

        if (\array_key_exists($user->getUserIdentifier(), $this->voterIds)) {
            $this->vote -= QotdVote::from($this->voterIds[$user->getUserIdentifier()])->toInt();
        }
        $this->vote += $vote->toInt();
        $this->voterIds[$user->getUserIdentifier()] = $vote->value;
    }

    public function hasVotedUp(UserInterface $user): bool
    {
        return QotdVote::Up === $this->getVote($user);
    }

    public function hasVotedDown(UserInterface $user): bool
    {
        return QotdVote::Down === $this->getVote($user);
    }

    public function getVote(UserInterface $user): QotdVote
    {
        if (!$this->voterIds || !\array_key_exists($user->getUserIdentifier(), $this->voterIds)) {
            return QotdVote::Null;
        }

        return QotdVote::from($this->voterIds[$user->getUserIdentifier()]);
    }

    #[Groups(['qotd:read'])]
    public function getImageUrls(): array
    {
        return array_map(fn (string $image) => sprintf('uploads/%s---%s', $this->id, $image), $this->images);
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
