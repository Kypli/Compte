<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Category::class);
	}

	public function add(Category $entity, bool $flush = false): void
	{
		$this->getEntityManager()->persist($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	public function remove(Category $entity, bool $flush = false): void
	{
		$this->getEntityManager()->remove($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	/**
	 * Renvoie les opérations d'une SC pour un mois
	 */
	public function mycategories($compte_id, $sign): ?array
	{
		return $this->createQueryBuilder('x')
			->leftjoin('x.compte', 'co')

			->select('x')

			->where('co.id = :compte_id')
			->andWhere('x.sign = :sign')

			->setParameters([
				'compte_id' => $compte_id,
				'sign' => $sign,
			])

			->orderBy('x.sign, x.id', 'DESC')

			->getQuery()
			->getResult()
		;
	}

	/**
	 * Renvoie la dernière position des catégories d'un compte
	 */
	public function lastPos($compte_id): ?array
	{
		return $this->createQueryBuilder('x')
			->leftjoin('x.compte', 'co')

			->select('x.position')

			->where('co.id = :compte_id')

			->setParameter('compte_id', $compte_id)

			->orderBy('x.position', 'DESC')
			->setMaxResults(1)

			->getQuery()
			->getOneOrNullResult()
		;
	}
}
