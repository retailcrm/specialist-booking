<?php

namespace App\Service;

use App\Entity\Specialist;

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
}
