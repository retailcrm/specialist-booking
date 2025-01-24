<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Specialist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Specialist>
 */
class SpecialistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Specialist::class);
    }

    /**
     * @return Specialist[]
     */
    public function findByAccountOrderedByOrdering(Account $account): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.account = :account')
            ->setParameter('account', $account)
            ->orderBy('s.ordering', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
