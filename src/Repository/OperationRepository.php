<?php

namespace App\Repository;

use App\Entity\Operation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Operation>
 *
 * @method Operation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Operation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Operation[]    findAll()
 * @method Operation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OperationRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Operation::class);
	}

	public function add(Operation $entity, bool $flush = false): void
	{
		$this->getEntityManager()->persist($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	public function remove(Operation $entity, bool $flush = false): void
	{
		$this->getEntityManager()->remove($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}


	/**
	 * @return Operation[] Returns an array of Operation objects
	 */
	public function OperationsByDateAndCompte($compte_id, $year_start): array
	{
		$date_start = date($year_start.'/01/01 00:00:00');
		$date_end = date(($year_start + 1).'/01/01 00:00:00');

		return $this->createQueryBuilder('x')
			->join('x.subcategory', 'sc')
			->join('sc.category', 'ca')
			->join('ca.compte', 'co')

			->where('co.id = :compte_id')
			->andWhere('x.date >= :date_start AND x.date <= :date_end ')

			->setParameters([
				'compte_id' => $compte_id,
				'date_start' => $date_start,
				'date_end' => $date_end,
			])

			->orderBy('x.date', 'ASC')

			->getQuery()
			->getResult()
		;
	}
}
