<?php

namespace App\Repository;

use App\Entity\Mobilier;
use App\Entity\User;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Mobilier>
 *
 * @method Mobilier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Mobilier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Mobilier[]    findAll()
 * @method Mobilier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MobilierRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Mobilier::class);
	}

	public function add(Mobilier $entity, bool $flush = false): void
	{
		$this->getEntityManager()->persist($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	public function remove(Mobilier $entity, bool $flush = false): void
	{
		$this->getEntityManager()->remove($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	/**
	 * @return Mobilier[]
	 */
	public function findByUser(User $user): array
	{
		return $this->createQueryBuilder('mobilier')
			->andWhere('mobilier.user = :user')
			->setParameter('user', $user)
			->orderBy('mobilier.libelle', 'ASC')
			->addOrderBy('mobilier.id', 'ASC')
			->getQuery()
			->getResult()
		;
	}

	public function sumValueByUser(User $user): float
	{
		return (float) $this->createQueryBuilder('mobilier')
			->select('COALESCE(SUM(mobilier.valeur), 0)')
			->andWhere('mobilier.user = :user')
			->setParameter('user', $user)
			->getQuery()
			->getSingleScalarResult()
		;
	}
}
