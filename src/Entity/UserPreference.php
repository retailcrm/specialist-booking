<?php

namespace App\Entity;

use App\Repository\UserPreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Личные настройки пользователя CRM в модуле: страницы модуля живут в воркере
 * без своего хранилища, поэтому выбор менеджера (например, филиал календаря)
 * запоминается на сервере по id пользователя CRM.
 */
#[ORM\Entity(repositoryClass: UserPreferenceRepository::class)]
#[ORM\UniqueConstraint(name: 'user_preference_account_user_uniq', columns: ['account_id', 'crm_user_id'])]
class UserPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Account $account;

    #[ORM\Column]
    private int $crmUserId;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $calendarStoreCode = null;

    public function __construct(Account $account, int $crmUserId)
    {
        $this->account = $account;
        $this->crmUserId = $crmUserId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getCrmUserId(): int
    {
        return $this->crmUserId;
    }

    public function getCalendarStoreCode(): ?string
    {
        return $this->calendarStoreCode;
    }

    public function setCalendarStoreCode(?string $calendarStoreCode): static
    {
        $this->calendarStoreCode = $calendarStoreCode;

        return $this;
    }
}
