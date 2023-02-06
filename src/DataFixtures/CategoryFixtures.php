<?php

namespace App\DataFixtures;

use App\Entity\Category as Entity;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class CategoryFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
	public const CATEGORY_ADMIN_POS_1 = 'category_admin_pos_1';
	public const CATEGORY_ADMIN_POS_2 = 'category_admin_pos_2';
	public const CATEGORY_ADMIN_NEG_1 = 'category_admin_neg_1';
	public const CATEGORY_ADMIN_NEG_2 = 'category_admin_neg_2';

	public const CATEGORY_USER_POS_1 = 'category_user_pos_1';
	public const CATEGORY_USER_POS_2 = 'category_user_pos_2';
	public const CATEGORY_USER_NEG_1 = 'category_user_neg_1';
	public const CATEGORY_USER_NEG_2 = 'category_user_neg_2';

	public function load(ObjectManager $manager)
	{
		// Admin (+)
		$entity = new Entity();
		$entity
			->setLibelle('travail')
			->setYear(date('Y'))
			->setCompte($this->getReference(CompteFixtures::COMPTE_ADMIN))
		;
		$this->addReference(self::CATEGORY_ADMIN_POS_1, $entity);
		$manager->persist($entity);

		$entity = new Entity();
		$entity
			->setLibelle('assurance')
			->setPosition(2)
			->setYear(date('Y'))
			->setCompte($this->getReference(CompteFixtures::COMPTE_ADMIN))
		;
		$this->addReference(self::CATEGORY_ADMIN_POS_2, $entity);
		$manager->persist($entity);

		// Admin (-)
		$entity = new Entity();
		$entity
			->setLibelle('loisirs')
			->setSign(false)
			->setYear(date('Y'))
			->setCompte($this->getReference(CompteFixtures::COMPTE_ADMIN))
		;
		$this->addReference(self::CATEGORY_ADMIN_NEG_1, $entity);
		$manager->persist($entity);

		// Admin (-)
		$entity = new Entity();
		$entity
			->setLibelle('maison')
			->setSign(false)
			->setPosition(2)
			->setYear(date('Y'))
			->setCompte($this->getReference(CompteFixtures::COMPTE_ADMIN))
		;
		$this->addReference(self::CATEGORY_ADMIN_NEG_2, $entity);
		$manager->persist($entity);

		// User (+)
		$entity = new Entity();
		$entity
			->setLibelle('travail')
			->setYear(date('Y'))
			->setCompte($this->getReference(CompteFixtures::COMPTE_USER))
		;
		$this->addReference(self::CATEGORY_USER_POS_1, $entity);
		$manager->persist($entity);

		$entity = new Entity();
		$entity
			->setLibelle('assurance')
			->setPosition(2)
			->setYear(date('Y'))
			->setCompte($this->getReference(CompteFixtures::COMPTE_USER))
		;
		$this->addReference(self::CATEGORY_USER_POS_2, $entity);
		$manager->persist($entity);

		// User (-)
		$entity = new Entity();
		$entity
			->setLibelle('loisir')
			->setSign(false)
			->setYear(date('Y'))
			->setCompte($this->getReference(CompteFixtures::COMPTE_USER))
		;
		$this->addReference(self::CATEGORY_USER_NEG_1, $entity);
		$manager->persist($entity);

		$entity = new Entity();
		$entity
			->setLibelle('maison')
			->setSign(false)
			->setPosition(2)
			->setYear(date('Y'))
			->setCompte($this->getReference(CompteFixtures::COMPTE_USER))
		;
		$this->addReference(self::CATEGORY_USER_NEG_2, $entity);
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
