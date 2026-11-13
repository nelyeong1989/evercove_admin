<?php

namespace App\Security\Voter;

use App\Entity\Payment;
use App\Entity\Reservation;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class RecordOwnershipVoter extends Voter
{
    public const EDIT = 'RECORD_EDIT';
    public const DELETE = 'RECORD_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE], true)
            && ($subject instanceof Reservation || $subject instanceof Payment);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Admins can edit and delete all records
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Staff can only edit/delete if they are the original creator
        /** @var Reservation|Payment $subject */
        $creator = $subject->getCreatedBy();

        return $creator !== null && $creator->getUserIdentifier() === $user->getUserIdentifier();
    }
}