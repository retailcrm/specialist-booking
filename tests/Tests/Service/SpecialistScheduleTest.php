<?php

namespace App\Tests\Tests\Service;

use App\Entity\Specialist;
use App\Service\DTO\DaySlots;
use App\Service\SpecialistSchedule;
use App\Tests\Mock\Service\SpecialistBusySlotFetcher;
use PHPUnit\Framework\TestCase;

class SpecialistScheduleTest extends TestCase
{
    public function testGetNearestDaySchedule(): void
    {
        // Arrange
        $now = new \DateTimeImmutable('2025-01-16 14:00:00');
        $schedule = new SpecialistSchedule(
            new SpecialistBusySlotFetcher(),
            $now
        );

        // Create test specialists
        $specialist1 = new Specialist('John Doe');
        $specialist1->setId(1);

        $specialist2 = new Specialist('Jane Smith');
        $specialist2->setId(2);

        $specialist3 = new Specialist('Jane Smith 3');
        $specialist3->setId(3);

        $result = $schedule->getNearestDaySchedule([$specialist1, $specialist2, $specialist3]);
        $this->assertCount(3, $result);

        // Check specialist 1 slots
        $this->assertArrayHasKey(1, $result);
        $specialist1Slots = $result[1];
        $this->assertInstanceOf(DaySlots::class, $specialist1Slots);
        $this->assertEquals('2025-01-16', $specialist1Slots->getDate());
        $this->assertSame(
            ['16:00', '17:00'],
            array_map(
                fn (\DateTimeImmutable $slot) => $slot->format('H:i'),
                $specialist1Slots->getSlots()
            )
        );

        // Check specialist 2 slots
        $this->assertArrayHasKey(2, $result);
        $specialist2Slots = $result[2];
        $this->assertInstanceOf(DaySlots::class, $specialist2Slots);
        $this->assertEquals('2025-01-16', $specialist2Slots->getDate());
        $this->assertSame(
            ['14:00', '15:00', '16:00', '17:00'],
            array_map(
                fn (\DateTimeImmutable $slot) => $slot->format('H:i'),
                $specialist2Slots->getSlots()
            )
        );

        // Check specialist 3 slots
        $this->assertArrayHasKey(3, $result);
        $specialist3Slots = $result[3];
        $this->assertInstanceOf(DaySlots::class, $specialist3Slots);
        $this->assertEquals('2025-01-17', $specialist3Slots->getDate());
        $this->assertSame(
            ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00'],
            array_map(
                fn (\DateTimeImmutable $slot) => $slot->format('H:i'),
                $specialist3Slots->getSlots()
            )
        );
    }
}
