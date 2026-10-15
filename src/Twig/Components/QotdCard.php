<?php

namespace App\Twig\Components;

use App\Entity\Qotd;
use App\Repository\Model\QotdDirection;
use App\Repository\Model\QotdVote;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponse;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent]
final class QotdCard
{
    use DefaultActionTrait;

    #[LiveProp]
    public Qotd $qotd;

    /** The list the card is displayed in, if any. */
    #[LiveProp]
    public ?QotdDirection $direction = null;

    /**
     * Turbo Streams replace the card by this id, e.g. after an edit.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    #[PostMount]
    public function postMount(array $data): array
    {
        $data['id'] ??= 'qotd-' . $this->qotd->id;

        return $data;
    }

    #[LiveAction]
    public function vote(
        #[LiveArg] QotdVote $vote,
        #[CurrentUser] UserInterface $user,
        EntityManagerInterface $em,
    ): ?LiveResponse {
        $this->qotd->applyVote($vote, $user);

        $em->flush();

        // A voted QOTD leaves the list of QOTD not voted yet
        if (QotdDirection::NotVoted === $this->direction && QotdVote::Null !== $this->qotd->getVote($user)) {
            return LiveResponse::remove();
        }

        return null;
    }

    /**
     * Not a vote() argument: Stimulus would parse the "null" action parameter as null.
     */
    #[LiveAction]
    public function resetVote(#[CurrentUser] UserInterface $user, EntityManagerInterface $em): void
    {
        $this->qotd->applyVote(QotdVote::Null, $user);

        $em->flush();
    }
}
