<?php

namespace App\Controller\Response;

use App\Entity\AccountSettings;

final readonly class AdminSettings implements \JsonSerializable
{
    public function __construct(
        private int $slotDuration,
        private bool $chooseStore,
        private bool $chooseCity,
        private string $clientId,
    ) {
    }

    /** clientId нужен настройщику AI-агента: им действия агента адресуют подключение модуля. */
    public static function fromEntity(AccountSettings $settings, string $clientId): self
    {
        return new self(
            $settings->getSlotDuration(),
            $settings->chooseStore(),
            $settings->chooseCity(),
            $clientId,
        );
    }

    /**
     * @return array{slotDuration: int, chooseStore: bool, chooseCity: bool, clientId: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'slotDuration' => $this->slotDuration,
            'chooseStore' => $this->chooseStore,
            'chooseCity' => $this->chooseCity,
            'clientId' => $this->clientId,
        ];
    }
}
