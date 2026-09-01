<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Specialist;
use App\Entity\Specialty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Specialty>
 */
class SpecialtyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Specialty::class);
    }

    public function findByAccountOrderingByNameQueryBuilder(Account $account): QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.account = :account')
            ->setParameter('account', $account)
            ->orderBy('s.name', 'ASC')
        ;
    }

    /**
     * @return array<array{name: string, cnt: int}>
     */
    public function getNamesWithSpecialistCount(Account $account): array
    {
        return $this->createQueryBuilder('sp')
            ->select('sp.name AS name, COUNT(s.id) AS cnt')
            ->leftJoin(Specialist::class, 's', Join::WITH, 's.specialty = sp')
            ->andWhere('sp.account = :account')
            ->setParameter('account', $account)
            ->groupBy('sp.id')
            ->orderBy('sp.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
