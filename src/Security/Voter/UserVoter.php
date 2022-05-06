<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{
    public const OWNER = 'OWNER';

    protected function supports(string $attribute, $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return ($attribute === self::OWNER)
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $client = $token->getUser();
        // if the client is anonymous, do not grant access
        if (!$client instanceof UserInterface) {
            return false;
        }

        if (!$subject instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::OWNER => ($subject->getClient() === $client),
            default => false,
        };
    }
}
