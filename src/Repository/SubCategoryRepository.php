<?php

namespace App\Repository;

use App\Entity\SubCategory;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<SubCategory>
 *
 * @method SubCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubCategory[]    findAll()
 * @method SubCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubCategoryRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, SubCategory::class);
	}

	public function add(SubCategory $entity, bool $flush = false): void
	{
		$this->getEntityManager()->persist($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	public function remove(SubCategory $entity, bool $flush = false): void
	{
		$this->getEntityManager()->remove($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	/**
	 * Renvoie les opérations selon compte, année et number positif/négatif
	 */
	public function getSubcategories($category_id): array
	{
		return $this->createQueryBuilder('x')
			->leftjoin('x.category', 'c')

			->where('c.id = :category_id')

			->setParameters([
				'category_id' => $category_id,
			])

			->orderBy('x.id', 'ASC')

			->getQuery()
			->getArrayResult()
		;
	}

	/**
	 * Renvoie les opérations selon compte, année et number positif/négatif
	 */
	public function idsFromCat($category_id): array
	{
		$q = $this->createQueryBuilder('x')
			->leftjoin('x.category', 'c')

			->select('x.id')

			->where('c.id = :category_id')

			->setParameters([
				'category_id' => $category_id,
			])

			->getQuery()
			->getResult()
		;

		return array_flip(array_map('current', $q));
	}
}
