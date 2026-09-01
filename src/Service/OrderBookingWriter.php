<?php

namespace App\Service;

use App\Entity\Specialist;
use RetailCrm\Api\Model\Entity\Orders\Order;
use RetailCrm\Api\Model\Entity\Orders\SerializedRelationCustomer;
use RetailCrm\Api\Model\Request\Orders\OrdersCreateRequest;
use RetailCrm\Api\Model\Request\Orders\OrdersEditRequest;

final readonly class OrderBookingWriter implements OrderBookingWriterInterface
{
    public function __construct(
        private AccountManager $accountManager,
    ) {
    }

    public function book(
        Specialist $specialist,
        \DateTimeImmutable $dateTime,
        ?int $orderId,
        array $customer,
        ?string $site = null,
        ?int $customerId = null,
    ): array {
        $client = $this->accountManager->getClient();

        $order = new Order();
        $order->customFields = [
            CustomFieldManager::CUSTOM_FIELD_SPECIALIST_CODE => $specialist->getDictionaryElementCode(),
            CustomFieldManager::CUSTOM_FIELD_DATETIME_CODE => $dateTime->format('Y-m-d H:i:s'),
        ];

        if (null !== $orderId) {
            $request = new OrdersEditRequest();
            $request->by = 'id';
            $request->order = $order;
            // CRM с несколькими магазинами требует site и на правке заказа
            $site ??= $this->resolveSiteCode();
            if (null !== $site) {
                $request->site = $site;
            }

            $response = $client->orders->edit($orderId, $request);

            return ['id' => (int) $response->id, 'number' => $response->order->number];
        }

        if (isset($customer['first_name'])) {
            $order->firstName = $customer['first_name'];
        }
        if (isset($customer['phone'])) {
            $order->phone = $customer['phone'];
        }
        if (isset($customer['comment'])) {
            $order->customerComment = $customer['comment'];
        }
        if (null !== $customerId) {
            $relation = new SerializedRelationCustomer();
            $relation->id = $customerId;
            $relation->type = 'customer';
            $order->customer = $relation;
        }

        $request = new OrdersCreateRequest();
        $request->order = $order;
        // сайт передаёт вызывающая сторона (агент знает канал клиента);
        // без него — первый сайт аккаунта, что верно только для односайтовых
        $site ??= $this->resolveSiteCode();
        if (null !== $site) {
            $request->site = $site;
        }

        $response = $client->orders->create($request);

        return ['id' => (int) $response->id, 'number' => $response->order->number];
    }

    private function resolveSiteCode(): ?string
    {
        $sites = $this->accountManager->getClient()->references->sites()->sites;
        foreach ($sites as $site) {
            if (null !== $site->code) {
                return $site->code;
            }
        }

        return null;
    }
}
