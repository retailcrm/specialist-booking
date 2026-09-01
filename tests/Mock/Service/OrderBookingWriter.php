<?php

namespace App\Tests\Mock\Service;

use App\Entity\Specialist;
use App\Service\OrderBookingWriterInterface;

class OrderBookingWriter implements OrderBookingWriterInterface
{
    /** @var array<array{specialist: string, datetime: string, orderId: ?int, customer: array<string, string>, site: ?string, customerId: ?int}> */
    public array $calls = [];

    public function book(
        Specialist $specialist,
        \DateTimeImmutable $dateTime,
        ?int $orderId,
        array $customer,
        ?string $site = null,
        ?int $customerId = null,
    ): array {
        $this->calls[] = [
            'specialist' => $specialist->getDictionaryElementCode(),
            'datetime' => $dateTime->format('Y-m-d H:i'),
            'orderId' => $orderId,
            'customer' => $customer,
            'site' => $site,
            'customerId' => $customerId,
        ];

        return ['id' => 42, 'number' => '42C'];
    }
}
