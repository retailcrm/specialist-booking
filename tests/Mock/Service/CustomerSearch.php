<?php

namespace App\Tests\Mock\Service;

use App\Service\CustomerSearchInterface;

class CustomerSearch implements CustomerSearchInterface
{
    public function search(string $query, int $limit = 20): array
    {
        return [
            ['id' => 5, 'firstName' => 'Иван', 'lastName' => 'Петров', 'phone' => '+79990000000'],
        ];
    }
}
