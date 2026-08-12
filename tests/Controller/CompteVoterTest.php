<?php

namespace App\Tests\Security;

use App\Entity\Compte;
use App\Entity\User;
use App\Security\CompteVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CompteVoterTest extends TestCase
{
	private CompteVoter $voter;

	protected function setUp(): void
	{
		$this->voter = new CompteVoter();
	}

	public function testOwnerCanAccessAccount(): void
	{
		$owner = $this->createUser('owner');
		$compte = (new Compte())->addUser($owner);

		self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($owner, $compte));
	}

	public function testAnotherUserCannotAccessAccount(): void
	{
		$owner = $this->createUser('owner');
		$intruder = $this->createUser('intruder');
		$compte = (new Compte())->addUser($owner);

		self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($intruder, $compte));
	}

	public function testAdministratorCanAccessAnyAccount(): void
	{
		$owner = $this->createUser('owner');
		$admin = $this->createUser('admin', ['ROLE_ADMIN']);
		$compte = (new Compte())->addUser($owner);

		self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($admin, $compte));
	}

	private function createUser(string $name, array $roles = ['ROLE_USER']): User
	{
		return (new User())
			->setUserName($name)
			->setPassword('test-password')
			->setRoles($roles)
		;
	}

	private function vote(User $user, Compte $compte): int
	{
		$token = new UsernamePasswordToken($user, 'main', $user->getRoles());

		return $this->voter->vote($token, $compte, [CompteVoter::ACCESS]);
	}
}
