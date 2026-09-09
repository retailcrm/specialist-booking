<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\UserPreference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPreference>
 */
class UserPreferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPreference::class);
    }

    public function findByUser(Account $account, int $crmUserId): ?UserPreference
    {
        return $this->findOneBy(['account' => $account, 'crmUserId' => $crmUserId]);
    }

    public function getOrCreate(Account $account, int $crmUserId): UserPreference
    {
        $preference = $this->findByUser($account, $crmUserId);
        if (null === $preference) {
            $preference = new UserPreference($account, $crmUserId);
            $this->getEntityManager()->persist($preference);
        }

        return $preference;
    }
}
