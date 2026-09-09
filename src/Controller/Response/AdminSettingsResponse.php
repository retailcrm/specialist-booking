<?php

namespace App\Controller\Response;

use App\Entity\Account;

final readonly class AdminSettingsResponse implements \JsonSerializable
{
    public function __construct(
        private AdminSettings $settings,
    ) {
    }

    public static function fromAccount(Account $account): self
    {
        return new self(AdminSettings::fromEntity($account->getSettings(), $account->getClientId()));
    }

    /**
     * @return array{settings: AdminSettings}
     */
    public function jsonSerialize(): array
    {
        return ['settings' => $this->settings];
    }
}
