<?php

namespace App\DataFixtures;

use App\Entity\Category as Entity;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class CategoryFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
	public const CATEGORY_ADMIN = 'category_admin';
	public const CATEGORY_USER = 'category_user';

	public function load(ObjectManager $manager)
	{
		// Admin
		$entity = new Entity();
		$entity
			->setLibelle('category 1')
			->setCompte($this->getReference(CompteFixtures::COMPTE_ADMIN))
		;
		$this->addReference(self::CATEGORY_ADMIN, $entity);
		$manager->persist($entity);

		// User
		$entity = new Entity();
		$entity
			->setLibelle('category 1')
			->setCompte($this->getReference(CompteFixtures::COMPTE_USER))
		;
		$this->addReference(self::CATEGORY_USER, $entity);
		$manager->persist($entity);

		$manager->flush();
	}

	public function getDependencies()
	{
		return [
			CompteFixtures::class,
		];
	}

	public static function getGroups(): array
	{
		return ['test'];
	}
}
