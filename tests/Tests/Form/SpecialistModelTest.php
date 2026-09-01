<?php

namespace App\Tests\Tests\Form;

use App\Form\Model\SpecialistModel;
use PHPUnit\Framework\TestCase;

class SpecialistModelTest extends TestCase
{
    public function testWorkTimesRoundTrip(): void
    {
        $text = "1: 10:00-13:00, 14:00-18:00\n6: 10:00-15:00";
        $parsed = SpecialistModel::parseWorkTimes($text);

        $this->assertSame([
            1 => [['10:00', '13:00'], ['14:00', '18:00']],
            6 => [['10:00', '15:00']],
        ], $parsed);
        $this->assertSame($text, SpecialistModel::formatWorkTimes($parsed));
    }

    public function testEmptyMeansNoPersonalSchedule(): void
    {
        $this->assertNull(SpecialistModel::parseWorkTimes(null));
        $this->assertNull(SpecialistModel::parseWorkTimes("  \n "));
        $this->assertNull(SpecialistModel::parseNonWorkingDays(''));
        $this->assertNull(SpecialistModel::formatWorkTimes(null));
    }

    public function testNonWorkingDaysRoundTrip(): void
    {
        // одиночный день хранится парой одинаковых дат, как в системе
        $parsed = SpecialistModel::parseNonWorkingDays('09.04-09.18, 12.31');

        $this->assertSame([['09.04', '09.18'], ['12.31', '12.31']], $parsed);
        $this->assertSame(
            '09.04-09.18, 12.31',
            SpecialistModel::formatNonWorkingDays($parsed)
        );
    }

    public function testBadInputThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SpecialistModel::parseWorkTimes('8: 10:00-18:00');
    }

    public function testBadIntervalThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SpecialistModel::parseWorkTimes('1: 10-18');
    }
}
