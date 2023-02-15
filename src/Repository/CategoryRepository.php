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
	 * Renvoie libellés, position et id des catégories d'un compte avant la position d'une categorie
	 */
	public function mycategoriesBefore($compte_id, $sign, $cat_pos): ?array
	{
		return $this->createQueryBuilder('x')
			->leftjoin('x.compte', 'co')

			->select([
				'x.id',
				'x.libelle',
				'x.position',
				'x.sign',
			])

			->where('co.id = :compte_id')
			->andWhere('x.sign = :sign')
			->andWhere('x.position < :cat_pos')

			->setParameters([
				'compte_id' => $compte_id,
				'sign' => $sign,
				'cat_pos' => $cat_pos,
			])

			->orderBy('x.position', 'ASC')

			->getQuery()
			->getResult()
		;
	}

	/**
	 * Renvoie libellés, position et id des catégories d'un compte après la position d'une categorie
	 */
	public function mycategoriesAfter($compte_id, $sign, $cat_pos): ?array
	{
		return $this->createQueryBuilder('x')
			->leftjoin('x.compte', 'co')

			->select([
				'x.id',
				'x.libelle',
				'x.position',
				'x.sign',
			])

			->where('co.id = :compte_id')
			->andWhere('x.sign = :sign')
			->andWhere('x.position > :cat_pos')

			->setParameters([
				'compte_id' => $compte_id,
				'sign' => $sign,
				'cat_pos' => $cat_pos,
			])

			->orderBy('x.position', 'ASC')

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

	/**
	 * Renvoie les position après la position d'une catégory
	 */
	public function getAllPosFromCompte($compte_id, $cat_id, $sign, $year): ?array
	{
		return $this->createQueryBuilder('x')
			->leftjoin('x.compte', 'co')

			->select([
				'x.id',
				'x.position',
				'x.libelle',
			])

			->where('co.id = :compte_id')
			->andWhere('x.id != :cat_id')
			->andWhere('x.sign = :sign')
			->andWhere('x.year = :year')

			->setParameters([
				'compte_id' => $compte_id,
				'cat_id' => $cat_id,
				'sign' => $sign,
				'year' => $year,
			])

			->orderBy('x.position', 'ASC')
			->addOrderBy('x.id', 'DESC')

			->getQuery()
			->getArrayResult()
		;
	}
}
