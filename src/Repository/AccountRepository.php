<?php

namespace App\Repository;

use App\Entity\Account;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Account>
 */
class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    public function getByClientId(string $clientId): ?Account
    {
        return $this->findOneBy(['clientId' => $clientId]);
    }

    public function getByUrl(string $url): ?Account
    {
        return $this->findOneBy(['url' => Account::normalizeUrl($url)]);
    }
}
