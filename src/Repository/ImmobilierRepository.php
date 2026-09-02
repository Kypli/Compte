<?php

namespace App\Repository;

use App\Entity\Immobilier;
use App\Entity\User;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Immobilier>
 *
 * @method Immobilier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Immobilier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Immobilier[]    findAll()
 * @method Immobilier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImmobilierRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Immobilier::class);
	}

	public function add(Immobilier $entity, bool $flush = false): void
	{
		$this->getEntityManager()->persist($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	public function remove(Immobilier $entity, bool $flush = false): void
	{
		$this->getEntityManager()->remove($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	/**
	 * @return Immobilier[]
	 */
	public function findByUser(User $user): array
	{
		return $this->createQueryBuilder('immobilier')
			->andWhere('immobilier.user = :user')
			->setParameter('user', $user)
			->orderBy('immobilier.libelle', 'ASC')
			->addOrderBy('immobilier.id', 'ASC')
			->getQuery()
			->getResult()
		;
	}

	public function sumValueByUser(User $user): float
	{
		return (float) $this->createQueryBuilder('immobilier')
			->select('COALESCE(SUM(immobilier.valeur), 0)')
			->andWhere('immobilier.user = :user')
			->setParameter('user', $user)
			->getQuery()
			->getSingleScalarResult()
		;
	}
}
