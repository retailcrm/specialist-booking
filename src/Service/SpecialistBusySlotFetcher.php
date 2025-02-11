<?php

namespace App\Service;

use App\Entity\AccountSettings;
use App\Entity\Specialist;
use App\Service\DTO\DaySlots;
use Doctrine\ORM\EntityManagerInterface;
use RetailCrm\Api\Model\Filter\Orders\OrderFilter;
use RetailCrm\Api\Model\Request\Orders\OrdersRequest;

final readonly class SpecialistBusySlotFetcher implements SpecialistBusySlotFetcherInterface
{
    private const int LIMIT = 50;

    public function __construct(
        private EntityManagerInterface $em,
        private AccountManager $accountManager,
    ) {
    }

    public function fetch(Specialist $specialist, \DateTimeImmutable $startDate, \DateTimeImmutable $endingDate): array
    {
        $client = $this->accountManager->getClient();

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
            $orders = $client->orders->list($request);
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
        $settings = $this->getSettings();
        $workTimes = $settings->getWorkTimes();

        if (null === $workTimes) {
            $this->updateSettings();
            $workTimes = $settings->getWorkTimes() ?? [];
        }

        return $workTimes;
    }

    public function getNonWorkingDays(): array
    {
        $settings = $this->getSettings();
        $nonWorkingDays = $settings->getNonWorkingDays();

        if (null === $nonWorkingDays) {
            $this->updateSettings();
            $nonWorkingDays = $settings->getNonWorkingDays() ?? [];
        }

        return $nonWorkingDays;
    }

    public function getSlotDuration(): int
    {
        return $this->getSettings()->getSlotDuration();
    }

    public function getStores(): array
    {
        return $this->accountManager->getClient()->references->stores()->stores;
    }

    private function updateSettings(): void
    {
        $crmSettings = $this->accountManager->getClient()->settings->get()->settings;

        $this->getSettings()->setFromCrmSettings($crmSettings);
        $this->em->flush();
    }

    private function getSettings(): AccountSettings
    {
        return $this->accountManager
            ->getAccount()
            ->getSettings()
        ;
    }
}
