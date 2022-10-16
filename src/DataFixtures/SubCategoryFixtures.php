<?php

namespace App\DataFixtures;

use App\Entity\SubCategory as Entity;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class SubCategoryFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{

	public const SUBCATEGORY_ADMIN_1 = 'subcategory_admin_1';
	public const SUBCATEGORY_ADMIN_2 = 'subcategory_admin_2';
	public const SUBCATEGORY_USER_1 = 'subcategory_user_1';
	public const SUBCATEGORY_USER_2 = 'subcategory_user_2';

	public function load(ObjectManager $manager)
	{
		// Admin
		$entity = new Entity();
		$entity
			->setLibelle('sub-category 1')
			->setCategory($this->getReference(CategoryFixtures::CATEGORY_ADMIN))
		;
		$this->addReference(self::SUBCATEGORY_ADMIN_1, $entity);
		$manager->persist($entity);

		$entity = new Entity();
		$entity
			->setLibelle('sub-category 2')
			->setCategory($this->getReference(CategoryFixtures::CATEGORY_ADMIN))
		;
		$this->addReference(self::SUBCATEGORY_ADMIN_2, $entity);
		$manager->persist($entity);

		// User
		$entity = new Entity();
		$entity
			->setLibelle('sub-category 1')
			->setCategory($this->getReference(CategoryFixtures::CATEGORY_USER))
		;
		$this->addReference(self::SUBCATEGORY_USER_1, $entity);
		$manager->persist($entity);

		$entity = new Entity();
		$entity
			->setLibelle('sub-category 2')
			->setCategory($this->getReference(CategoryFixtures::CATEGORY_USER))
		;
		$this->addReference(self::SUBCATEGORY_USER_2, $entity);
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
