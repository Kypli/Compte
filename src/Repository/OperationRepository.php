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
	public function OperationsByYearAndCompteAndSign($compte_id, $year, $sign = true): array
	{
		$date_start = date($year.'/01/01 00:00:00');
		$date_end = date(($year).'/12/31 23:59:59');

		return $this->createQueryBuilder('x')
			->leftjoin('x.subcategory', 'sc')
			->leftjoin('sc.category', 'ca')
			->leftjoin('ca.compte', 'co')

			->where('co.id = :compte_id')
			->andWhere('ca.sign = :sign')
			->andWhere('x.date >= :date_start AND x.date <= :date_end')

			->setParameters([
				'sign' => $sign,
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
	public function CompteSoldeActuel($compte_id, $sign): ?float
	{
		$date = new \Datetime('now');

		return $this->createQueryBuilder('x')
			->leftjoin('x.subcategory', 'sc')
			->leftjoin('sc.category', 'ca')
			->leftjoin('ca.compte', 'co')

			->select('SUM(x.number)')

			->where('co.id = :compte_id')
			->andWhere('x.anticipe = false')
			->andWhere('ca.sign = :sign')
			->andWhere('x.date <= :date')

			->setParameters([
				'compte_id' => $compte_id,
				'date' => $date,
				'sign' => $sign,
			])

			->getQuery()
			->getSingleScalarResult()
		;
	}

	/**
	 * Renvoie les opérations d'une SC pour un mois
	 */
	public function gestion($sc, $year, $month, $sign, $daysInMonth): ?array
	{
		$sign = $sign == 'pos' ? true : false;
		$date_start = new \Datetime($year.'/'.$month.'/01 00:00:00');
		$date_end = new \Datetime($year.'/'.$month.'/'.$daysInMonth.' 23:59:59');

		return $this->createQueryBuilder('x')
			->leftjoin('x.subcategory', 'sc')
			->leftjoin('sc.category', 'ca')

			->select([
				'x.id',
				'x.number',
				'DAY(x.date) as day',
				'MONTH(x.date) as month',
				'YEAR(x.date) as year',
				'x.anticipe',
				'x.comment',
			])

			->where('sc.id = :sc')
			->andWhere('x.date IS NOT NULL')
			->andWhere('x.date >= :date_start')
			->andWhere('x.date <= :date_end')
			->andWhere('ca.sign = :sign')

			->setParameters([
				'sc' => $sc,
				'date_end' => $date_end,
				'date_start' => $date_start,
				'sign' => $sign,
			])

			->orderBy('x.date', 'DESC')

			->getQuery()
			->getArrayResult()
		;
	}
}
