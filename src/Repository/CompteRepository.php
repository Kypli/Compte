<?php

namespace App\Repository;

use App\Entity\Compte;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Compte>
 *
 * @method Compte|null find($id, $lockMode = null, $lockVersion = null)
 * @method Compte|null findOneBy(array $criteria, array $orderBy = null)
 * @method Compte[]    findAll()
 * @method Compte[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompteRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Compte::class);
	}

	public function add(Compte $entity, bool $flush = false): void
	{
		$this->getEntityManager()->persist($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	public function remove(Compte $entity, bool $flush = false): void
	{
		$this->getEntityManager()->remove($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

   /**
	 * @return Operation[] Returns an array of Operation objects
	 */
   public function getOperationsByDateAndCompte($compte_id, $year): array
   {
	   return $this->createQueryBuilder('x')
	   ->join
		   ->andWhere('o.exampleField = :val')
		   ->setParameter('val', $value)
		   ->orderBy('o.id', 'ASC')
		   ->setMaxResults(10)
		   ->getQuery()
		   ->getResult()
	   ;
   }
}
