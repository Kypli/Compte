<?php

namespace App\DataFixtures;

use App\Entity\UserProfil as Entity;
use App\Entity\User;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class UserProfilFixtures extends Fixture implements FixtureGroupInterface
{
	public function load(ObjectManager $manager): void
	{
		// Admin
		$entity = new Entity();
		$entity
			->setUser($this->getReference(UserFixtures::USER_ADMIN, User::class))
			->setNom('admin-nom')
			->setPrenom('admin-prenom')
			->setMail('admin@yahoo.fr')
			->setAdresse('10 rue des admins')
			->setVille('AdminLand')
			->setPays('France')
			->setCodePostal('12345')
			->setTelephone('06 06 06 06 06')
		;
		$manager->persist($entity);

		// User
		$entity = new Entity();
		$entity
			->setUser($this->getReference(UserFixtures::USER_USER, User::class))
			->setNom('user-nom')
			->setPrenom('user-prenom')
			->setMail('user@yahoo.fr')
			->setAdresse('6 rue des users')
			->setVille('User-city')
			->setPays('France')
			->setCodePostal('67890')
			->setTelephone('01 01 01 01 01')
		;
		$manager->persist($entity);

		$manager->flush();
	}

	public function getDependencies(): array
	{
		return [
			UserFixtures::class,
		];
	}

	public static function getGroups(): array
	{
		return ['dev'];
	}
}
