<?php

namespace App\DataFixtures;

use App\Entity\Compte as Entity;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class CompteFixtures extends Fixture implements FixtureGroupInterface
{
	public const COMPTE_ADMIN = 'compte_admin';
	public const COMPTE_USER = 'compte_user';

	public function load(ObjectManager $manager)
	{
		// Admin
		$entity = new Entity();
		$entity
			->setLibelle('Compte admin')
		;
		$this->addReference(self::COMPTE_ADMIN, $entity);
		$manager->persist($entity);

		// User
		$entity = new Entity();
		$entity
			->setLibelle('Compte user')
		;
		$this->addReference(self::COMPTE_USER, $entity);
		$manager->persist($entity);

		$manager->flush();
	}

	public static function getGroups(): array
	{
		return ['test'];
	}
}
