<?php

namespace App\Service;

use App\Entity\Specialist;

interface OrderBookingWriterInterface
{
    /**
     * Записывает бронь в заказ CRM: обновляет существующий заказ или создаёт новый.
     *
     * @param array{first_name?: string, phone?: string, comment?: string} $customer
     * @param ?int                                                         $customerId id клиента CRM — новый заказ привязывается к нему,
     *                                                                                 чтобы заказ был виден в заказах клиента чата
     *
     * @return array{id: int, number: ?string} внутренний id и публичный номер заказа
     */
    public function book(
        Specialist $specialist,
        \DateTimeImmutable $dateTime,
        ?int $orderId,
        array $customer,
        ?string $site = null,
        ?int $customerId = null,
    ): array;
}
