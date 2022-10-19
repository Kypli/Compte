<?php

namespace App\DataFixtures;

use App\Entity\UserPreference as Entity;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class UserPreferencesFixtures extends Fixture implements FixtureGroupInterface
{
	public function load(ObjectManager $manager)
	{
		// Admin
		$entity = new Entity();
		$entity
			->setUser($this->getReference(UserFixtures::USER_ADMIN))
		;
		$manager->persist($entity);

		// User
		$entity = new Entity();
		$entity
			->setUser($this->getReference(UserFixtures::USER_USER))
		;
		$manager->persist($entity);

		$manager->flush();
	}

	public function getDependencies()
	{
		return [
			UserFixtures::class,
		];
	}

	public static function getGroups(): array
	{
		return ['test'];
	}
}
