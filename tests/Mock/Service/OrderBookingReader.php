<?php

namespace App\Tests\Mock\Service;

use App\Service\DTO\Booking;
use App\Service\OrderBookingReaderInterface;

class OrderBookingReader implements OrderBookingReaderInterface
{
    /** Одна запись у первого специалиста в 12:00 первого дня периода. */
    public function fetch(array $specialistCodes, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        if ([] === $specialistCodes) {
            return [];
        }

        return [
            new Booking(
                $specialistCodes[0],
                $from->setTime(12, 0),
                777,
                'B-777',
                'Петров Иван',
                '+79990000000',
                'new',
            ),
        ];
    }

    public function statusNames(): array
    {
        return ['new' => 'Новый'];
    }
}
