<?php

namespace App\DataFixtures;

use App\Entity\SubCategory as Entity;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class SubCategoryFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
	public const SUBCATEGORY_ADMIN_POS_1_1 = 'subcategory_admin_pos_1_1';
	public const SUBCATEGORY_ADMIN_POS_1_2 = 'subcategory_admin_pos_1_2';
	public const SUBCATEGORY_ADMIN_POS_1_3 = 'subcategory_admin_pos_1_3';
	public const SUBCATEGORY_ADMIN_POS_2_1 = 'subcategory_admin_pos_2_1';
	public const SUBCATEGORY_ADMIN_POS_2_2 = 'subcategory_admin_pos_2_2';
	public const SUBCATEGORY_ADMIN_NEG_1_1 = 'subcategory_admin_neg_1_1';
	public const SUBCATEGORY_ADMIN_NEG_1_2 = 'subcategory_admin_neg_1_2';
	public const SUBCATEGORY_ADMIN_NEG_1_3 = 'subcategory_admin_neg_1_3';
	public const SUBCATEGORY_ADMIN_NEG_2_1 = 'subcategory_admin_neg_2_1';
	public const SUBCATEGORY_ADMIN_NEG_2_2 = 'subcategory_admin_neg_2_2';

	public const SUBCATEGORY_USER_POS_1_1 = 'subcategory_user_pos_1_1';
	public const SUBCATEGORY_USER_POS_1_2 = 'subcategory_user_pos_1_2';
	public const SUBCATEGORY_USER_POS_1_3 = 'subcategory_user_pos_1_3';
	public const SUBCATEGORY_USER_POS_2_1 = 'subcategory_user_pos_2_1';
	public const SUBCATEGORY_USER_POS_2_2 = 'subcategory_user_pos_2_2';
	public const SUBCATEGORY_USER_NEG_1_1 = 'subcategory_user_neg_1_1';
	public const SUBCATEGORY_USER_NEG_1_2 = 'subcategory_user_neg_1_2';
	public const SUBCATEGORY_USER_NEG_1_3 = 'subcategory_user_neg_1_3';
	public const SUBCATEGORY_USER_NEG_2_1 = 'subcategory_user_neg_2_1';
	public const SUBCATEGORY_USER_NEG_2_2 = 'subcategory_user_neg_2_2';

	public function load(ObjectManager $manager)
	{

		$datas = [
			1 => [
				'setRef' => CategoryFixtures::CATEGORY_ADMIN_POS_1,
				'addRef' => self::SUBCATEGORY_ADMIN_POS_1_1,
				'libelle' => "salaire",
				'position' => 1,
			],
			2 => [
				'setRef' => CategoryFixtures::CATEGORY_ADMIN_POS_1,
				'addRef' => self::SUBCATEGORY_ADMIN_POS_1_2,
				'libelle' => "prime",
				'position' => 2,
			],
			3 => [
				'setRef' => CategoryFixtures::CATEGORY_ADMIN_POS_1,
				'addRef' => self::SUBCATEGORY_ADMIN_POS_1_3,
				'libelle' => "13eme mois",
				'position' => 3,
			],
			4 => [
				'setRef' => CategoryFixtures::CATEGORY_ADMIN_POS_2,
				'addRef' => self::SUBCATEGORY_ADMIN_POS_2_1,
				'libelle' => "CPAM",
				'position' => 1,
			],
			5 => [
				'setRef' => CategoryFixtures::CATEGORY_ADMIN_POS_2,
				'addRef' => self::SUBCATEGORY_ADMIN_POS_2_2,
				'libelle' => "crédit",
				'position' => 2,
			],
			6 => [
				'setRef' => CategoryFixtures::CATEGORY_ADMIN_NEG_1,
				'addRef' => self::SUBCATEGORY_ADMIN_NEG_1_1,
				'libelle' => "jeux",
				'position' => 1,
			],
			7 => [
				'setRef' => CategoryFixtures::CATEGORY_ADMIN_NEG_1,
				'addRef' => self::SUBCATEGORY_ADMIN_NEG_1_2,
				'libelle' => "cinéma",
				'position' => 2,
			],
			8 => [
				'setRef' => CategoryFixtures::CATEGORY_ADMIN_NEG_1,
				'addRef' => self::SUBCATEGORY_ADMIN_NEG_1_3,
				'libelle' => "sorties",
				'position' => 3,
			],
			9 => [
				'setRef' => CategoryFixtures::CATEGORY_ADMIN_NEG_2,
				'addRef' => self::SUBCATEGORY_ADMIN_NEG_2_1,
				'libelle' => "entretien",
				'position' => 1,
			],
			10 => [
				'setRef' => CategoryFixtures::CATEGORY_ADMIN_NEG_2,
				'addRef' => self::SUBCATEGORY_ADMIN_NEG_2_2,
				'libelle' => "meubles",
				'position' => 2,
			],
			11 => [
				'setRef' => CategoryFixtures::CATEGORY_USER_POS_1,
				'addRef' => self::SUBCATEGORY_USER_POS_1_1,
				'libelle' => "salaire",
				'position' => 1,
			],
			12 => [
				'setRef' => CategoryFixtures::CATEGORY_USER_POS_1,
				'addRef' => self::SUBCATEGORY_USER_POS_1_2,
				'libelle' => "prime",
				'position' => 2,
			],
			13 => [
				'setRef' => CategoryFixtures::CATEGORY_USER_POS_1,
				'addRef' => self::SUBCATEGORY_USER_POS_1_3,
				'libelle' => "13eme mois",
				'position' => 3,
			],
			14 => [
				'setRef' => CategoryFixtures::CATEGORY_USER_POS_2,
				'addRef' => self::SUBCATEGORY_USER_POS_2_1,
				'libelle' => "CPAM",
				'position' => 1,
			],
			15 => [
				'setRef' => CategoryFixtures::CATEGORY_USER_POS_2,
				'addRef' => self::SUBCATEGORY_USER_POS_2_2,
				'libelle' => "crédit",
				'position' => 2,
			],
			16 => [
				'setRef' => CategoryFixtures::CATEGORY_USER_NEG_1,
				'addRef' => self::SUBCATEGORY_USER_NEG_1_1,
				'libelle' => "jeux",
				'position' => 1,
			],
			17 => [
				'setRef' => CategoryFixtures::CATEGORY_USER_NEG_1,
				'addRef' => self::SUBCATEGORY_USER_NEG_1_2,
				'libelle' => "cinéma",
				'position' => 2,
			],
			18 => [
				'setRef' => CategoryFixtures::CATEGORY_USER_NEG_1,
				'addRef' => self::SUBCATEGORY_USER_NEG_1_3,
				'libelle' => "sorties",
				'position' => 3,
			],
			19 => [
				'setRef' => CategoryFixtures::CATEGORY_USER_NEG_2,
				'addRef' => self::SUBCATEGORY_USER_NEG_2_1,
				'libelle' => "entretien",
				'position' => 1,
			],
			20 => [
				'setRef' => CategoryFixtures::CATEGORY_USER_NEG_2,
				'addRef' => self::SUBCATEGORY_USER_NEG_2_2,
				'libelle' => "meubles",
				'position' => 2,
			],
		];

		foreach ($datas as $key => $value){

			$entity = new Entity();
			$entity
				->setLibelle($value['libelle'])
				->setPosition($value['position'])
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
