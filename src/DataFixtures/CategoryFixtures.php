<?php

namespace App\DataFixtures;

use App\Entity\Category as Entity;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class CategoryFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
	public const CATEGORY_ADMIN_POS = 'category_admin_pos';
	public const CATEGORY_ADMIN_NEG = 'category_admin_neg';
	public const CATEGORY_USER_POS = 'category_user_pos';
	public const CATEGORY_USER_NEG = 'category_user_neg';

	public function load(ObjectManager $manager)
	{
		// Admin
		$entity = new Entity();
		$entity
			->setLibelle('travail')
			->setCompte($this->getReference(CompteFixtures::COMPTE_ADMIN))
			->isSign(true)
		;
		$this->addReference(self::CATEGORY_ADMIN_POS, $entity);
		$manager->persist($entity);

		$entity = new Entity();
		$entity
			->setLibelle('loisirs')
			->setCompte($this->getReference(CompteFixtures::COMPTE_ADMIN))
			->isSign(false)
		;
		$this->addReference(self::CATEGORY_ADMIN_NEG, $entity);
		$manager->persist($entity);

		// User
		$entity = new Entity();
		$entity
			->setLibelle('travail')
			->setCompte($this->getReference(CompteFixtures::COMPTE_USER))
			->isSign(true)
		;
		$this->addReference(self::CATEGORY_USER_POS, $entity);
		$manager->persist($entity);

		$entity = new Entity();
		$entity
			->setLibelle('loisir')
			->setCompte($this->getReference(CompteFixtures::COMPTE_USER))
			->isSign(false)
		;
		$this->addReference(self::CATEGORY_USER_NEG, $entity);
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
