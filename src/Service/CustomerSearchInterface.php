<?php

namespace App\Service;

interface CustomerSearchInterface
{
    /**
     * Поиск клиентов CRM по имени, телефону или email — тот же фильтр, что
     * в списке клиентов системы.
     *
     * @return array<array{id: int, firstName: ?string, lastName: ?string, phone: ?string}>
     */
    public function search(string $query, int $limit = 20): array;
}
