<?php

namespace App\Controller\Response;

final readonly class Settings implements \JsonSerializable
{
    public function __construct(
        private bool $chooseStore,
        private bool $chooseCity,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'chooseStore' => $this->chooseStore,
            'chooseCity' => $this->chooseCity,
        ];
    }
}
