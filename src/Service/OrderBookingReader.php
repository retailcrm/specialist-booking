<?php

namespace App\Service;

use App\Service\DTO\Booking;
use RetailCrm\Api\Model\Filter\Orders\OrderFilter;
use RetailCrm\Api\Model\Request\Orders\OrdersRequest;

final readonly class OrderBookingReader implements OrderBookingReaderInterface
{
    private const int LIMIT = 100;

    public function __construct(
        private AccountManager $accountManager,
    ) {
    }

    public function fetch(array $specialistCodes, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        if ([] === $specialistCodes) {
            return [];
        }

        $filter = new OrderFilter();
        $filter->customFields = [
            CustomFieldManager::CUSTOM_FIELD_SPECIALIST_CODE => $specialistCodes,
            CustomFieldManager::CUSTOM_FIELD_DATETIME_CODE => [
                'min' => $from->format('Y-m-d'),
                'max' => $to->format('Y-m-d'),
            ],
        ];

        $request = new OrdersRequest();
        $request->limit = self::LIMIT;
        $request->page = 1;
        $request->filter = $filter;

        $client = $this->accountManager->getClient();
        $result = [];
        do {
            $orders = $client->orders->list($request);
            foreach ($orders->orders as $order) {
                $code = $order->customFields[CustomFieldManager::CUSTOM_FIELD_SPECIALIST_CODE] ?? null;
                $dt = $order->customFields[CustomFieldManager::CUSTOM_FIELD_DATETIME_CODE] ?? null;
                if (!is_string($code) || !is_string($dt) || !in_array($code, $specialistCodes, true)) {
                    continue;
                }

                $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dt);
                if (false === $dateTime) {
                    continue;
                }

                $name = trim(implode(' ', array_filter([$order->lastName, $order->firstName])));

                $result[] = new Booking(
                    $code,
                    $dateTime,
                    (int) $order->id,
                    $order->number,
                    '' === $name ? null : $name,
                    $order->phone,
                    $order->status,
                );
            }
        } while ($orders->pagination->totalPageCount > ++$request->page);

        usort($result, static fn (Booking $a, Booking $b): int => $a->dateTime <=> $b->dateTime);

        return $result;
    }

    public function statusNames(): array
    {
        $names = [];
        foreach ($this->accountManager->getClient()->references->statuses()->statuses as $status) {
            if (null !== $status->code && null !== $status->name) {
                $names[$status->code] = $status->name;
            }
        }

        return $names;
    }
}
