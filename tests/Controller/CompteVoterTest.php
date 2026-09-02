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
	private int $nextUserId = 1;

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

	public function testAssociatedUserWithNoAccessCannotOpenAccount(): void
	{
		$owner = $this->createUser('owner');
		$restrictedUser = $this->createUser('restricted');
		$compte = (new Compte())
			->addUser($owner)
			->addUser($restrictedUser)
			->setUserSharing($restrictedUser, 'none', true)
		;

		self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($restrictedUser, $compte));
	}

	public function testObserverCanAccessButCannotEditAccount(): void
	{
		$owner = $this->createUser('owner');
		$observer = $this->createUser('observer');
		$compte = (new Compte())
			->addUser($owner)
			->addUser($observer)
			->setUserSharing($observer, 'observer', false)
		;

		self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($observer, $compte));
		self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($observer, $compte, CompteVoter::EDIT));
	}

	public function testEditorCanAccessAndEditAccount(): void
	{
		$owner = $this->createUser('owner');
		$editor = $this->createUser('editor');
		$compte = (new Compte())
			->addUser($owner)
			->addUser($editor)
			->setUserSharing($editor, 'editor', false)
		;

		self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($editor, $compte));
		self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($editor, $compte, CompteVoter::EDIT));
	}

	private function createUser(string $name, array $roles = ['ROLE_USER']): User
	{
		$user = (new User())
			->setUserName($name)
			->setPassword('test-password')
			->setRoles($roles)
		;
		(new \ReflectionProperty(User::class, 'id'))->setValue($user, $this->nextUserId++);

		return $user;
	}

	private function vote(User $user, Compte $compte, string $attribute = CompteVoter::ACCESS): int
	{
		$token = new UsernamePasswordToken($user, 'main', $user->getRoles());

		return $this->voter->vote($token, $compte, [$attribute]);
	}
}
