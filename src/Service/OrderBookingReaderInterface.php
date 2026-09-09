<?php

namespace App\Service;

use App\Service\DTO\Booking;

interface OrderBookingReaderInterface
{
    /**
     * Записи из заказов CRM по кодам специалистов за период.
     *
     * @param string[] $specialistCodes коды элементов словаря специалистов
     *
     * @return Booking[]
     */
    public function fetch(array $specialistCodes, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /**
     * Названия статусов заказов CRM по кодам — записи несут только код.
     *
     * @return array<string, string>
     */
    public function statusNames(): array;
}
