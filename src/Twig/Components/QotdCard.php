<?php

namespace App\Twig\Components;

use App\Entity\MyTop;
use App\Entity\Qotd;
use App\Qotd\Exception\MyTopFullException;
use App\Repository\Model\QotdDirection;
use App\Repository\Model\QotdVote;
use App\Repository\MyTopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponse;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent]
final class QotdCard
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    public const string MY_TOP_CHANGED_EVENT = 'qotd:my-top-changed';
    public const string VOTED_EVENT = 'qotd:voted';

    #[LiveProp]
    public Qotd $qotd;

    /** The list the card is displayed in, if any. */
    #[LiveProp]
    public ?QotdDirection $direction = null;

    public function __construct(
        private readonly MyTopRepository $myTopRepository,
        private readonly Security $security,
        private readonly LoggerInterface $logger,
    ) {
    }

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

        $this->emit(self::VOTED_EVENT);

        // A voted QOTD leaves the list of QOTD not voted yet
        if (QotdDirection::NotVoted === $this->direction && QotdVote::Null !== $this->qotd->getVote($user)) {
            return LiveResponse::remove();
        }

        return null;
    }

    public function getMyTop(): MyTop
    {
        $user = $this->security->getUser() ?? throw new \LogicException('The top requires a user.');

        return $this->myTopRepository->getForUser($user);
    }

    #[LiveAction]
    public function toggleMyTop(EntityManagerInterface $em): void
    {
        $myTop = $this->getMyTop();

        try {
            $myTop->toggle($this->qotd);
        } catch (MyTopFullException $e) {
            // The page was rendered before the top got full, e.g. in another tab: the re-render disables the button
            $this->logger->warning('Unable to add a QOTD to a top.', ['exception' => $e]);

            return;
        }

        $em->persist($myTop);
        $em->flush();

        $this->emit(self::MY_TOP_CHANGED_EVENT);
    }

    /**
     * Not a vote() argument: Stimulus would parse the "null" action parameter as null.
     */
    #[LiveAction]
    public function resetVote(#[CurrentUser] UserInterface $user, EntityManagerInterface $em): void
    {
        $this->qotd->applyVote(QotdVote::Null, $user);

        $em->flush();

        $this->emit(self::VOTED_EVENT);
    }
}
