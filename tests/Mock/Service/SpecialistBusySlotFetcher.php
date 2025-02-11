<?php

namespace App\Tests\Mock\Service;

use App\Entity\Specialist;
use App\Service\DTO\DaySlots;
use App\Service\SpecialistBusySlotFetcherInterface;
use RetailCrm\Api\Model\Entity\References\Store;
use RetailCrm\Api\Model\Entity\References\StoreAddress;

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
            1 => [['09:00', '13:00'], ['14:00', '17:00']],
            2 => [['09:00', '13:00'], ['14:00', '18:00']],
            3 => [['09:00', '13:00'], ['14:00', '18:00']],
            4 => [['09:00', '13:00'], ['14:00', '18:00']],
            5 => [['09:00', '13:00'], ['14:00', '17:00']],
        ];
    }

    public function getNonWorkingDays(): array
    {
        return [
            ['01.17', '01.17'],
        ];
    }

    public function getSlotDuration(): int
    {
        return 60;
    }

    public function getStores(): array
    {
        $s1a = new StoreAddress();
        $s1a->city = 'Moscow';
        $s2a = new StoreAddress();
        $s2a->city = 'Tula';

        $s1 = new Store();
        $s1->code = 'store1';
        $s1->name = 'Store 1';
        $s1->address = $s1a;

        $s2 = new Store();
        $s2->code = 'store2';
        $s2->name = 'Store 2';
        $s2->address = $s1a;

        $s3 = new Store();
        $s3->code = 'store3';
        $s3->name = 'Store 3';
        $s3->address = $s2a;

        $s4 = new Store();
        $s4->code = 'store4';
        $s4->name = 'Store 4';

        return [$s1, $s2, $s3, $s4];
    }
}
