<?php

namespace App\Service\DTO;

final readonly class DaySlots
{
    /**
     * @param \DateTimeImmutable[] $slots
     */
    public function __construct(
        private string $date,
        private array $slots,
    ) {
    }

    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @return \DateTimeImmutable[]
     */
    public function getSlots(): array
    {
        return $this->slots;
    }
}
