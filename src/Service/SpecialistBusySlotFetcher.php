<?php

namespace App\Service;

use App\Entity\Specialist;
use App\Service\DTO\DaySlots;
use RetailCrm\Api\Client;
use RetailCrm\Api\Model\Filter\Orders\OrderFilter;
use RetailCrm\Api\Model\Request\Orders\OrdersRequest;

final readonly class SpecialistBusySlotFetcher implements SpecialistBusySlotFetcherInterface
{
    private const int LIMIT = 50;

    public function __construct(
        private Client $client,
    ) {
    }

    public function fetch(Specialist $specialist, \DateTimeImmutable $startDate, \DateTimeImmutable $endingDate): array
    {
        $filter = new OrderFilter();
        $filter->customFields = [
            CustomFieldManager::CUSTOM_FIELD_SPECIALIST_CODE => [$specialist->getDictionaryElementCode()],
            CustomFieldManager::CUSTOM_FIELD_DATETIME_CODE => [
                'min' => $startDate->format('Y-m-d'),
                'max' => $endingDate->format('Y-m-d'),
            ],
        ];

        $request = new OrdersRequest();
        $request->limit = self::LIMIT;
        $request->page = 1;
        $request->filter = $filter;

        $dates = [];
        do {
            $orders = $this->client->orders->list($request);
            foreach ($orders->orders as $order) {
                $specialistCode = $order->customFields[CustomFieldManager::CUSTOM_FIELD_SPECIALIST_CODE] ?? null;
                // на всякий проверяем
                if ($specialistCode !== $specialist->getDictionaryElementCode()) {
                    continue;
                }

                $dt = $order->customFields[CustomFieldManager::CUSTOM_FIELD_DATETIME_CODE] ?? null;
                if (null === $dt) {
                    continue;
                }

                $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dt);
                if (false === $dateTime) {
                    continue;
                }

                $date = $dateTime->format('Y-m-d');
                if (!isset($dates[$date])) {
                    $dates[$date] = [];
                }

                $dates[$date][] = $dateTime;
            }
        } while ($orders->pagination->totalPageCount > ++$request->page);

        ksort($dates);

        $result = [];
        foreach ($dates as $date => $slots) {
            $result[] = new DaySlots($date, $slots);
        }

        return $result;
    }

    public function getCompanyWorkingTime(): array
    {
        return [
            1 => [['09:00', '20:00']],
            2 => [['09:00', '20:00']],
            3 => [['09:00', '20:00']],
            4 => [['09:00', '20:00']],
            5 => [['09:00', '20:00']],
        ];
    }
}
