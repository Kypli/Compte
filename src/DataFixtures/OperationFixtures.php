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

	public function load(ObjectManager $manager): void
	{
		$subcategories = [
			SubCategoryFixtures::SUBCATEGORY_ADMIN_POS_1_1,
			SubCategoryFixtures::SUBCATEGORY_ADMIN_POS_1_2,
			SubCategoryFixtures::SUBCATEGORY_ADMIN_POS_1_3,
			SubCategoryFixtures::SUBCATEGORY_ADMIN_POS_2_1,
			SubCategoryFixtures::SUBCATEGORY_ADMIN_POS_2_2,
			SubCategoryFixtures::SUBCATEGORY_ADMIN_NEG_1_1,
			SubCategoryFixtures::SUBCATEGORY_ADMIN_NEG_1_2,
			SubCategoryFixtures::SUBCATEGORY_ADMIN_NEG_1_3,
			SubCategoryFixtures::SUBCATEGORY_ADMIN_NEG_2_1,
			SubCategoryFixtures::SUBCATEGORY_ADMIN_NEG_2_2,
			SubCategoryFixtures::SUBCATEGORY_USER_POS_1_1,
			SubCategoryFixtures::SUBCATEGORY_USER_POS_1_2,
			SubCategoryFixtures::SUBCATEGORY_USER_POS_1_3,
			SubCategoryFixtures::SUBCATEGORY_USER_POS_2_1,
			SubCategoryFixtures::SUBCATEGORY_USER_POS_2_2,
			SubCategoryFixtures::SUBCATEGORY_USER_NEG_1_1,
			SubCategoryFixtures::SUBCATEGORY_USER_NEG_1_2,
			SubCategoryFixtures::SUBCATEGORY_USER_NEG_1_3,
			SubCategoryFixtures::SUBCATEGORY_USER_NEG_2_1,
			SubCategoryFixtures::SUBCATEGORY_USER_NEG_2_2,
			SubCategoryFixtures::SUBCATEGORY_ADMIN_LIQUIDE_POS_1_1,
			SubCategoryFixtures::SUBCATEGORY_ADMIN_LIQUIDE_NEG_1_1,
		];

		for($i = 0; $i <= 500; $i++){

			$year = date('Y');
			$month = rand(1, 12);
			$day = rand(1, 28);
			$hour = rand(0, 23);
			$minute = rand(0, 59);
			$second = rand(0, 59);
			$date = new \Datetime($year.'/'.$month.'/'.$day.' '.$hour.':'.$minute.':'.$second);

			$date_now = new \Datetime('now');
			$date_now_month = $date_now->format('m');

			if ($month > $date_now->format('n')){
				$anticipe = 1;

			} elseif($month < $date_now->format('n')){
				$rand = rand(1, 100);
				$anticipe = $rand <= 5
					? 1
					: 0
				;

			} else {
				$anticipe = rand(0, 1);
			}

			$subcategory = $subcategories[array_rand($subcategories)];

			$entity = new Entity();
			$entity
				->setNumber(rand(0, 10000) / 100)
				->setAnticipe($anticipe)
				->setDate($date)
				->setComment('comment '.$i)
				->setDateLastAction($date_now)
				->setLastAction('create')
				->setSubcategory($this->getReference($subcategory))
			;
			$manager->persist($entity);
		}

		$cashOperations = [
			[
				'number' => 120,
				'anticipe' => false,
				'date' => new \DateTime('first day of this month 10:00'),
				'comment' => 'Retrait pour les dépenses courantes',
				'subcategory' => SubCategoryFixtures::SUBCATEGORY_ADMIN_LIQUIDE_POS_1_1,
			],
			[
				'number' => 38.40,
				'anticipe' => false,
				'date' => new \DateTime('first day of this month 18:00 +4 days'),
				'comment' => 'Achats en espèces',
				'subcategory' => SubCategoryFixtures::SUBCATEGORY_ADMIN_LIQUIDE_NEG_1_1,
			],
		];

		foreach ($cashOperations as $cashOperation) {
			$entity = new Entity();
			$entity
				->setNumber($cashOperation['number'])
				->setAnticipe($cashOperation['anticipe'])
				->setDate($cashOperation['date'])
				->setComment($cashOperation['comment'])
				->setDateLastAction(new \DateTime())
				->setLastAction('create')
				->setSubcategory($this->getReference($cashOperation['subcategory']))
			;
			$manager->persist($entity);
		}

		$manager->flush();
	}

	public function getDependencies(): array
	{
		return [
			SubCategoryFixtures::class,
		];
	}

	public static function getGroups(): array
	{
		return ['dev'];
	}
}
