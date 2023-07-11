<?php

namespace App\Repository;

use App\Entity\User;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, User::class);
	}

	/**
	 * Used to upgrade (rehash) the user's password automatically over time.
	 */
	public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
	{
		if (!$user instanceof User) {
			throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
		}

		$user->setPassword($newEncodedPassword);
		$this->_em->persist($user);
		$this->_em->flush();
	}

	public function add(User $entity, bool $flush = false): void
	{
		$this->getEntityManager()->persist($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	public function remove(User $entity, bool $flush = false): void
	{
		$this->getEntityManager()->remove($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	/**
	 * @return Renvoie le nombre d'admin
	 */
	public function countAdmin()
	{
		return $this->createQueryBuilder('u')
			->where('u.roles LIKE :role')
			->setParameter('role', '%ROLE_ADMIN%')
			->select('COUNT(u.id)')
			->getQuery()
			->getSingleScalarResult()
		;
	}

	/**
	 * @return Renvoie le nombre d'anonyme
	 */
	public function countAnonymous()
	{
		return $this->createQueryBuilder('u')
			->where('u.anonyme = TRUE')
			->select('COUNT(u.id)')
			->getQuery()
			->getSingleScalarResult()
		;
	}

	/**
	 * @return Renvoie le login du dernier anonyme
	 */
	public function getLastAnonyme()
	{
		return $this->createQueryBuilder('u')
			->where('u.anonyme = TRUE')
			->select('u.userName')
			->setMaxResults(1)
			->orderBy('u.id', 'DESC')
			->getQuery()
			->getOneOrNullResult()
		;
	}
}
