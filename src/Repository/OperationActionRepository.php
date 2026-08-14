<?php

namespace App\Repository;

use App\Entity\Operation;
use App\Entity\OperationAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OperationAction>
 */
class OperationActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OperationAction::class);
    }

    public function add(OperationAction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush){
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OperationAction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush){
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return OperationAction[]
     */
    public function lastActionsForCompte(int $compteId, int $limit = 15): array
    {
        return $this->accountQuery($compteId)
            ->orderBy('action.actionAt', 'DESC')
            ->addOrderBy('action.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findLatestForCompte(int $compteId): ?OperationAction
    {
        return $this->accountQuery($compteId)
            ->orderBy('action.actionAt', 'DESC')
            ->addOrderBy('action.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findReusableAnomalyResolution(Operation $operation, string $resolution): ?OperationAction
    {
        $actionType = 'delete' === $resolution ? 'del' : 'edit';
        foreach ($this->findBy(
            ['operation' => $operation, 'actionType' => $actionType, 'cancelled' => true],
            ['actionAt' => 'DESC', 'id' => 'DESC']
        ) as $action){
            $before = $action->getBeforeSnapshot();
            $after = $action->getAfterSnapshot();
            if (
                true !== ($before['anticipe'] ?? false)
                || true !== ($before['actif'] ?? false)
                || null === $after
            ){
                continue;
            }

            $matchesResolution = 'delete' === $resolution
                ? false === ($after['actif'] ?? true)
                : true === ($after['actif'] ?? false) && false === ($after['anticipe'] ?? true)
            ;
            if ($matchesResolution){
                return $action;
            }
        }

        return null;
    }

    private function accountQuery(int $compteId): QueryBuilder
    {
        return $this->createQueryBuilder('action')
            ->leftJoin('action.operation', 'operation')
            ->leftJoin('operation.subcategory', 'subcategory')
            ->leftJoin('subcategory.category', 'operationCategory')
            ->leftJoin('operationCategory.compte', 'operationCompte')
            ->leftJoin('action.category', 'movedCategory')
            ->leftJoin('movedCategory.compte', 'movedCompte')
            ->addSelect('operation', 'subcategory', 'operationCategory', 'movedCategory')
            ->where('operationCompte.id = :compteId OR movedCompte.id = :compteId')
            ->setParameter('compteId', $compteId)
        ;
    }
}
