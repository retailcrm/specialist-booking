<?php

namespace App\Service\DTO;

/**
 * Запись к специалисту, прочитанная из заказа CRM.
 */
final readonly class Booking
{
    public function __construct(
        public string $specialistCode,
        public \DateTimeImmutable $dateTime,
        public int $orderId,
        public ?string $orderNumber,
        public ?string $customerName,
        public ?string $phone,
        public ?string $statusCode,
    ) {
    }
}
