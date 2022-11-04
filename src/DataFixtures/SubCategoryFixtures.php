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
	public const SUBCATEGORY_ADMIN_3 = 'subcategory_admin_3';
	public const SUBCATEGORY_ADMIN_4 = 'subcategory_admin_4';
	public const SUBCATEGORY_USER_1 = 'subcategory_user_1';
	public const SUBCATEGORY_USER_2 = 'subcategory_user_2';
	public const SUBCATEGORY_USER_3 = 'subcategory_user_3';
	public const SUBCATEGORY_USER_4 = 'subcategory_user_4';

	public function load(ObjectManager $manager)
	{

		$datas = [
			1 => [
				'setRef' => CategoryFixtures::CATEGORY_ADMIN_POS,
				'addRef' => self::SUBCATEGORY_ADMIN_1,
				'libelle' => "salaire",
			],
			2 => [
				'setRef' => CategoryFixtures::CATEGORY_ADMIN_POS,
				'addRef' => self::SUBCATEGORY_ADMIN_2,
				'libelle' => "prime",
			],
			3 => [
				'setRef' => CategoryFixtures::CATEGORY_ADMIN_NEG,
				'addRef' => self::SUBCATEGORY_ADMIN_3,
				'libelle' => "jeux",
			],
			4 => [
				'setRef' => CategoryFixtures::CATEGORY_ADMIN_NEG,
				'addRef' => self::SUBCATEGORY_ADMIN_4,
				'libelle' => "cinéma",
			],
			5 => [
				'setRef' => CategoryFixtures::CATEGORY_USER_POS,
				'addRef' => self::SUBCATEGORY_USER_1,
				'libelle' => "salaire",
			],
			6 => [
				'setRef' => CategoryFixtures::CATEGORY_USER_POS,
				'addRef' => self::SUBCATEGORY_USER_2,
				'libelle' => "prime",
			],
			7 => [
				'setRef' => CategoryFixtures::CATEGORY_USER_NEG,
				'addRef' => self::SUBCATEGORY_USER_3,
				'libelle' => "jeux",
			],
			8 => [
				'setRef' => CategoryFixtures::CATEGORY_USER_NEG,
				'addRef' => self::SUBCATEGORY_USER_4,
				'libelle' => "cinéma",
			],
		];

		foreach ($datas as $key => $value){

			$entity = new Entity();
			$entity
				->setLibelle($value['libelle'])
				->setCategory($this->getReference($value['setRef']))
			;
			$this->addReference($value['addRef'], $entity);
			$manager->persist($entity);
		}
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
