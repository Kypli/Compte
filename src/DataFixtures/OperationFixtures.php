<?php

namespace App\DataFixtures;

use App\Entity\Operation as Entity;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class OperationFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
	public const CATEGORY_ADMIN = 'category_admin';
	public const CATEGORY_USER = 'category_user';

	public function load(ObjectManager $manager)
	{
		
		$date = new \Datetime('now');

		$subcategories = [
			SubCategoryFixtures::SUBCATEGORY_ADMIN_1,
			SubCategoryFixtures::SUBCATEGORY_ADMIN_2,
			SubCategoryFixtures::SUBCATEGORY_USER_1,
			SubCategoryFixtures::SUBCATEGORY_USER_2,
		];

		for($i = 0; $i <= 100; $i++){

			$subcategory = $subcategories[rand(0, 3)];

			$entity = new Entity();
			$entity
				->setNumber(rand(100, 10000) / 100)
				->setAnticipe(rand(0, 1))
				->setDate($date)
				->setComment('comment '.$i)
				->setSubcategory($this->getReference($subcategory))
			;
			$manager->persist($entity);
		}

		$manager->flush();
	}

	public function getDependencies()
	{
		return [
			SubCategoryFixtures::class,
		];
	}

	public static function getGroups(): array
	{
		return ['test'];
	}
}
