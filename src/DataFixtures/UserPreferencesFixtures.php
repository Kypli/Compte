<?php

namespace App\DataFixtures;

use App\Entity\UserPreference as Entity;
use App\Entity\User;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class UserPreferencesFixtures extends Fixture implements FixtureGroupInterface
{
	public function load(ObjectManager $manager): void
	{
		// Admin
		$entity = new Entity();
		$entity
			->setDashboardBackground('green')
			->setAccountBackground('green')
			->setTablePalette('classic')
			->setMoneyTrimZeros(true)
			->setShowEditableBorder(true)
			->setUser($this->getReference(UserFixtures::USER_ADMIN, User::class))
		;
		$manager->persist($entity);

		// User
		$entity = new Entity();
		$entity
			->setDashboardBackground('green')
			->setAccountBackground('green')
			->setTablePalette('classic')
			->setMoneyTrimZeros(true)
			->setShowEditableBorder(true)
			->setUser($this->getReference(UserFixtures::USER_USER, User::class))
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
