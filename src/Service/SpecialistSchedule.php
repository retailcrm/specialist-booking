<?php

namespace App\Service;

use App\Entity\Specialist;
use App\Service\DTO\DaySlots;

final readonly class SpecialistSchedule
{
    private const int SLOT_DURATION_IN_MINUTES = 60;

    public function __construct(
        private SpecialistBusySlotFetcherInterface $busySlotFetcher,
        private \DateTimeImmutable $now,
    ) {
    }

    /**
     * Returns for every specialist available time slots
     * in nearest working day of specialist with available slots
     *
     * @param Specialist[] $specialists
     *
     * @return array<int, DaySlots> - specialist id as key
     */
    public function getNearestDaySchedule(array $specialists): array
    {
        $result = [];
        $endDate = $this->now->modify('+7 days');

        foreach ($specialists as $specialist) {
            $daySlots = $this->getSpecialistNearestDaySlots($specialist, $this->now, $endDate);
            if (null !== $daySlots) {
                $result[(int) $specialist->getId()] = $daySlots;
            }
        }

        return $result;
    }

    private function getSpecialistNearestDaySlots(
        Specialist $specialist,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): ?DaySlots {
        $workingTime = $this->busySlotFetcher->getCompanyWorkingTime();

        $busySlots = $this->busySlotFetcher->fetch($specialist, $startDate, $endDate);
        $busySlotsMap = [];

        // Create a map of busy slots by date for faster lookup
        foreach ($busySlots as $daySlots) {
            $busySlotsMap[$daySlots->getDate()] = $daySlots->getSlots();
        }

        // Check each day until we find one with available slots
        $currentDate = $startDate;
        while ($currentDate <= $endDate) {
            $dayOfWeek = (int) $currentDate->format('N'); // 1 (Monday) to 7 (Sunday)

            // Skip if not a working day
            if (!isset($workingTime[$dayOfWeek])) {
                $currentDate = $currentDate->modify('+1 day');
                continue;
            }

            $availableSlots = [];
            $workingPeriods = $workingTime[$dayOfWeek];

            // Generate slots for each working period
            foreach ($workingPeriods as $period) {
                [$startTime, $endTime] = $period;
                $slotStart = \DateTimeImmutable::createFromFormat(
                    'Y-m-d H:i',
                    $currentDate->format('Y-m-d') . ' ' . $startTime
                );
                $slotEnd = \DateTimeImmutable::createFromFormat(
                    'Y-m-d H:i',
                    $currentDate->format('Y-m-d') . ' ' . $endTime
                );
                assert(false !== $slotStart && false !== $slotEnd);

                while ($slotStart < $slotEnd) {
                    $nextSlotStart = $slotStart->modify(sprintf('+%d minutes', self::SLOT_DURATION_IN_MINUTES));

                    // Skip slots in the past
                    if ($slotStart < $this->now) {
                        $slotStart = $nextSlotStart;
                        continue;
                    }

                    // Add slot if its end time doesn't exceed the working period end time
                    $slotEndTime = $slotStart->modify(sprintf('+%d minutes', self::SLOT_DURATION_IN_MINUTES));
                    if ($slotEndTime <= $slotEnd) {
                        $availableSlots[] = $slotStart;
                    }

                    $slotStart = $nextSlotStart;
                }
            }

            // Remove busy slots
            $dateKey = $currentDate->format('Y-m-d');
            if (isset($busySlotsMap[$dateKey])) {
                $busySlotTimes = array_map(
                    static fn (\DateTimeImmutable $dt) => $dt->format('H:i'),
                    $busySlotsMap[$dateKey]
                );

                $availableSlots = array_filter(
                    $availableSlots,
                    static function (\DateTimeImmutable $slot) use ($dateKey, $busySlotTimes) {
                        $slotEnd = $slot->modify(sprintf('+%d minutes', self::SLOT_DURATION_IN_MINUTES));

                        // Check if this slot intersects with any busy slot
                        foreach ($busySlotTimes as $busyTime) {
                            $busyStart = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $dateKey . ' ' . $busyTime);
                            assert(false !== $busyStart);

                            $busyEnd = $busyStart->modify(sprintf('+%d minutes', self::SLOT_DURATION_IN_MINUTES));

                            // If there's any overlap, the slot is not available
                            if ($slot < $busyEnd && $slotEnd > $busyStart) {
                                return false;
                            }
                        }

                        return true;
                    }
                );
            }

            // If we found available slots, return them
            if (!empty($availableSlots)) {
                return new DaySlots(
                    $dateKey,
                    array_values($availableSlots)
                );
            }

            $currentDate = $currentDate->modify('+1 day');
        }

        return null;
    }
}
