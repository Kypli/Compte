<?php

namespace App\DataFixtures;

use App\Entity\SubCategory as Entity;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class SubCategoryFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
	public function load(ObjectManager $manager)
	{
		// Admin
		$entity = new Entity();
		$entity
			->setLibelle('sub-category 1')
			->setCategory($this->getReference(CategoryFixtures::CATEGORY_ADMIN))
		;
		$manager->persist($entity);

		$entity = new Entity();
		$entity
			->setLibelle('sub-category 2')
			->setCategory($this->getReference(CategoryFixtures::CATEGORY_ADMIN))
		;
		$manager->persist($entity);

		// User
		$entity = new Entity();
		$entity
			->setLibelle('sub-category 1')
			->setCategory($this->getReference(CategoryFixtures::CATEGORY_USER))
		;
		$manager->persist($entity);

		$entity = new Entity();
		$entity
			->setLibelle('sub-category 2')
			->setCategory($this->getReference(CategoryFixtures::CATEGORY_USER))
		;
		$manager->persist($entity);

		$manager->flush();
	}

	public function getDependencies()
	{
		return [
			CategoryFixtures::class,
		];
	}

	public static function getGroups(): array
	{
		return ['test'];
	}
}
