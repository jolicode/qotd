<?php

namespace App\Entity;

use App\Qotd\Exception\MyTopFullException;
use App\Repository\MyTopRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * The favorite QOTD of a user, the best one first.
 */
#[ORM\Entity(repositoryClass: MyTopRepository::class)]
class MyTop
{
    public const int MAX_SIZE = 10;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true])]
    private array $qotdIds = [];

    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        public readonly string $userIdentifier,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getQotdIds(): array
    {
        return $this->qotdIds;
    }

    public function has(Qotd $qotd): bool
    {
        return \in_array($qotd->id, $this->qotdIds, true);
    }

    public function isFull(): bool
    {
        return \count($this->qotdIds) >= self::MAX_SIZE;
    }

    public function toggle(Qotd $qotd): void
    {
        if ($this->has($qotd)) {
            $this->remove($qotd);

            return;
        }

        if ($this->isFull()) {
            throw new MyTopFullException(\sprintf('The top of "%s" already holds %d QOTD.', $this->userIdentifier, self::MAX_SIZE));
        }

        $this->qotdIds[] = $qotd->id;
    }

    /**
     * @param positive-int $position Starts at 1. Past the end, the QOTD goes last.
     */
    public function move(Qotd $qotd, int $position): void
    {
        if (!$this->has($qotd)) {
            return;
        }

        $this->remove($qotd);
        array_splice($this->qotdIds, $position - 1, 0, [$qotd->id]);
    }

    private function remove(Qotd $qotd): void
    {
        $this->qotdIds = array_values(array_diff($this->qotdIds, [$qotd->id]));
    }
}
