<?php

namespace App\Service;

use App\Entity\Specialist;
use RetailCrm\Api\Model\Entity\References\Store;

interface SpecialistBusySlotFetcherInterface
{
    /**
     * @return array<DTO\DaySlots>
     */
    public function fetch(
        Specialist $specialist,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endingDate,
    ): array;

    /**
     * @return array<int, array<array{string, string}>>
     */
    public function getCompanyWorkingTime(): array;

    /**
     * @return array<array{string, string}> in format {'mm.dd', 'mm.dd'}
     */
    public function getNonWorkingDays(): array;

    /**
     * @return int in minutes
     */
    public function getSlotDuration(): int;

    /**
     * @return Store[]
     */
    public function getStores(): array;
}
