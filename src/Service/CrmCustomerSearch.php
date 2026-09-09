<?php

namespace App\Service;

use RetailCrm\Api\Model\Filter\Customers\CustomerFilter;
use RetailCrm\Api\Model\Request\Customers\CustomersRequest;

final readonly class CrmCustomerSearch implements CustomerSearchInterface
{
    public function __construct(
        private AccountManager $accountManager,
    ) {
    }

    public function search(string $query, int $limit = 20): array
    {
        $filter = new CustomerFilter();
        $filter->name = $query;

        $request = new CustomersRequest();
        $request->limit = $limit;
        $request->page = 1;
        $request->filter = $filter;

        $result = [];
        foreach ($this->accountManager->getClient()->customers->list($request)->customers as $customer) {
            $phone = null;
            foreach ($customer->phones as $customerPhone) {
                if (null !== $customerPhone->number && '' !== $customerPhone->number) {
                    $phone = $customerPhone->number;
                    break;
                }
            }

            $result[] = [
                'id' => (int) $customer->id,
                'firstName' => $customer->firstName,
                'lastName' => $customer->lastName,
                'phone' => $phone,
            ];
        }

        return $result;
    }
}
