<?php

namespace App\Service;

use App\Entity\Specialist;
use App\Service\DTO\DaySlots;

final readonly class SpecialistSchedule
{
    private const int SLOT_DURATION_IN_MINUTES = 60;

    public function __construct(
        private SpecialistBusySlotFetcherInterface $busySlotFetcher,
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
    public function getNearestDaySchedule(array $specialists, \DateTimeImmutable $now): array
    {
        $result = [];
        $endDate = $now->modify('+14 days');

        foreach ($specialists as $specialist) {
            $daySlots = $this->getSpecialistNearestDaySlots($specialist, $now, $endDate, $now);
            if (null !== $daySlots) {
                $result[(int) $specialist->getId()] = $daySlots;
            }
        }

        return $result;
    }

    /**
     * Return available time slots for specialist in given date range
     *
     * @return DaySlots[]
     */
    public function getSpecialistSlots(
        Specialist $specialist,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        \DateTimeImmutable $now,
    ): array {
        $startDate = $startDate->setTime(0, 0);
        if ($startDate < $now) {
            $startDate = clone $now;
        }

        $endDate = $endDate->setTime(23, 59);

        return $this->getAvailableSlots($specialist, $startDate, $endDate, $now);
    }

    private function getSpecialistNearestDaySlots(
        Specialist $specialist,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        \DateTimeImmutable $now,
    ): ?DaySlots {
        $slots = $this->getAvailableSlots($specialist, $startDate, $endDate, $now, true);

        return $slots[0] ?? null;
    }

    /**
     * Get available slots for a specialist in the given date range
     *
     * @return DaySlots[]
     */
    private function getAvailableSlots(
        Specialist $specialist,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        \DateTimeImmutable $now,
        bool $stopOnFirstDay = false,
    ): array {
        $workingTime = $this->busySlotFetcher->getCompanyWorkingTime();
        $busySlots = $this->busySlotFetcher->fetch($specialist, $startDate, $endDate);
        $nonWorkingDays = $this->busySlotFetcher->getNonWorkingDays();
        $result = [];

        // Create a map of busy slots by date for faster lookup
        $busySlotsMap = [];
        foreach ($busySlots as $daySlots) {
            $busySlotsMap[$daySlots->getDate()] = $daySlots->getSlots();
        }

        // Check each day in the range
        $currentDate = $startDate;
        while ($currentDate <= $endDate) {
            $dayOfWeek = (int) $currentDate->format('N'); // 1 (Monday) to 7 (Sunday)

            // Skip if not a working day
            if (!isset($workingTime[$dayOfWeek])) {
                $currentDate = $currentDate->modify('+1 day');
                continue;
            }

            foreach ($nonWorkingDays as [$startString, $endString]) {
                $nonWorkingStart = \DateTimeImmutable::createFromFormat('Y.m.d', $currentDate->format('Y.') . $startString);
                $nonWorkingEnd = \DateTimeImmutable::createFromFormat('Y.m.d', $currentDate->format('Y.') . $endString);
                if (false === $nonWorkingStart || false === $nonWorkingEnd) {
                    continue;
                }

                $nonWorkingStart = $nonWorkingStart->setTime(0, 0);
                $nonWorkingEnd = $nonWorkingEnd->setTime(0, 0)->modify('+1 day');

                if ($currentDate >= $nonWorkingStart && $currentDate < $nonWorkingEnd) {
                    $currentDate = $currentDate->modify('+1 day');
                    continue 2;
                }
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
                    if ($slotStart < $now) {
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

            // If we found available slots, add them to result
            if (!empty($availableSlots)) {
                $result[] = new DaySlots(
                    $dateKey,
                    array_values($availableSlots)
                );

                if ($stopOnFirstDay) {
                    break;
                }
            }

            $currentDate = $currentDate->modify('+1 day');
        }

        return $result;
    }
}
