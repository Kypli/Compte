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
	 * Renvoie les opérations selon compte, année et number positif/négatif
	 */
	public function OperationsByYearAndCompte($compte_id, $year_start, $pos = true): array
	{
		$type = $pos ? '>=' : '<';
		$date_start = date($year_start.'/01/01 00:00:00');
		$date_end = date(($year_start + 1).'/01/01 00:00:00');

		return $this->createQueryBuilder('x')
			->join('x.subcategory', 'sc')
			->join('sc.category', 'ca')
			->join('ca.compte', 'co')

			->where('co.id = :compte_id')
			->andWhere('x.number '.$type.' 0')
			->andWhere('x.date >= :date_start AND x.date <= :date_end')

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

	/**
	 * Renvoie le solde actuel
	 */
	public function CompteSoldeActuel($compte_id): ?float
	{
		$date = new \Datetime('now');

		return $this->createQueryBuilder('x')
			->leftjoin('x.subcategory', 'sc')
			->leftjoin('sc.category', 'ca')
			->leftjoin('ca.compte', 'co')

			->select('SUM(x.number)')

			->where('co.id = :compte_id')
			->andWhere('x.anticipe = false')
			->andWhere('x.date <= :date')

			->setParameters([
				'compte_id' => $compte_id,
				'date' => $date,
			])

			->getQuery()
			->getSingleScalarResult()
		;
	}

	/**
	 * Renvoie les opérations d'une SC pour un mois
	 */
	public function gestion($sc, $year, $month, $type, $anticipe): ?array
	{
		$type = $type == 'pos'
			? '>= 0'
			: '< 0'
		;

		$d = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$date_start = new \Datetime($year.'/'.$month.'/01 00:00:00');
		$date_end = new \Datetime($year.'/'.$month.'/'.$d.' 23:59:59');

		return $this->createQueryBuilder('x')
			->leftjoin('x.subcategory', 'sc')

			->select([
				'x.id',
				'x.number',
				'x.date',
				'x.anticipe',
				'x.comment',
			])

			->where('sc.id = :sc')
			->andWhere('x.date IS NOT NULL')
			->andWhere('x.date >= :date_start')
			->andWhere('x.date <= :date_end')
			->andWhere('x.number '.$type)
			->andWhere('x.anticipe = :anticipe')

			->setParameters([
				'sc' => $sc,
				'anticipe' => $anticipe,
				'date_end' => $date_end,
				'date_start' => $date_start,
			])

			->getQuery()
			->getArrayResult()
		;
	}
}
