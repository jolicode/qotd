<?php

namespace App\Security\Voter;

use App\Entity\Qotd;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class QotdVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, ['QOTD_VOTE'])
            && $subject instanceof Qotd;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case 'QOTD_VOTE':
                return !$subject->hasVoted($user);
        }

        return false;
    }
}
