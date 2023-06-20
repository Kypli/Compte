<?php 

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserChecker implements UserCheckerInterface
{
	public function checkPreAuth(UserInterface $user): void
	{
		if (!$user instanceof User){
			return;
		}

		if (!$user->isActive()){
			throw new CustomUserMessageAccountStatusException('bloque');
		}

		// if ($user->isInactif()){
		// 	throw new CustomUserMessageAccountStatusException('inactif');
		// }

		// if ($user->isDeleted()){
		// 	throw new CustomUserMessageAccountStatusException('delete');
		// }
	}

	public function checkPostAuth(UserInterface $user): void
	{
		if (!$user instanceof User){
			return;
		}

		if (!$user->isActive()){
			throw new CustomUserMessageAccountStatusException('bloque');
		}

		// if ($user->isExpired()){
		// 	throw new AccountExpiredException('...');
		// }
	}
}