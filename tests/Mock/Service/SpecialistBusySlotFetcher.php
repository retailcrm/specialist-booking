<?php

namespace App\Tests\Mock\Service;

use App\Entity\Specialist;
use App\Service\DTO\DaySlots;
use App\Service\SpecialistBusySlotFetcherInterface;

class SpecialistBusySlotFetcher implements SpecialistBusySlotFetcherInterface
{
    /** @var array<int, DaySlots[]> */
    private array $busySlots;

    public function __construct()
    {
        $this->busySlots = [
            1 => [
                new DaySlots('2025-01-16', [
                    new \DateTimeImmutable('2025-01-16 12:00'),
                    new \DateTimeImmutable('2025-01-16 14:30'),
                    new \DateTimeImmutable('2025-01-16 15:00'),
                    new \DateTimeImmutable('2025-01-16 18:00'),
                ]),
            ],
            3 => [
                new DaySlots('2025-01-16', [
                    new \DateTimeImmutable('2025-01-16 14:30'),
                    new \DateTimeImmutable('2025-01-16 15:30'),
                    new \DateTimeImmutable('2025-01-16 16:30'),
                    new \DateTimeImmutable('2025-01-16 17:30'),
                ]),
            ],
        ];
    }

    public function fetch(Specialist $specialist, \DateTimeImmutable $startDate, \DateTimeImmutable $endingDate): array
    {
        return $this->busySlots[$specialist->getId()] ?? [];
    }

    public function getCompanyWorkingTime(): array
    {
        return [
            1 => [['09:00', '13:00'], ['14:00', '18:00']],
            2 => [['09:00', '13:00'], ['14:00', '18:00']],
            3 => [['09:00', '13:00'], ['14:00', '18:00']],
            4 => [['09:00', '13:00'], ['14:00', '18:00']],
            5 => [['09:00', '13:00'], ['14:00', '17:00']],
        ];
    }
}
