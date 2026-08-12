<?php

namespace App\Security;

use App\Entity\Compte;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CompteVoter extends Voter
{
	public const ACCESS = 'COMPTE_ACCESS';

	protected function supports(string $attribute, mixed $subject): bool
	{
		return self::ACCESS === $attribute && $subject instanceof Compte;
	}

	protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
	{
		$user = $token->getUser();
		if (!$user instanceof User){
			return false;
		}

		if (in_array('ROLE_ADMIN', $user->getRoles(), true) || $subject->getUsers()->contains($user)){
			return true;
		}

		if (null === $user->getId()){
			return false;
		}

		foreach ($subject->getUsers() as $owner){
			if ($owner->getId() === $user->getId()){
				return true;
			}
		}

		return false;
	}
}
