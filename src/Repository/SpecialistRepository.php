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
    public function findByAccountOrderedByOrdering(Account $account, ?string $storeCode = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.account = :account')
            ->setParameter('account', $account)
            ->orderBy('s.ordering', 'ASC')
            ->addOrderBy('s.name', 'ASC')
        ;

        if (null !== $storeCode) {
            $qb
                ->andWhere('s.storeCode = :storeCode')
                ->setParameter('storeCode', $storeCode)
            ;
        }

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<array{code: string, cnt: int}>
     */
    public function getStoreCodes(Account $account): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.storeCode as code, COUNT(s.id) as cnt')
            ->where('s.storeCode IS NOT NULL')
            ->andWhere('s.account = :account')
            ->setParameter('account', $account)
            ->groupBy('s.storeCode')
            ->orderBy('cnt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
