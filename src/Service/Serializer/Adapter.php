<?php

namespace App\Service\Serializer;

interface Adapter
{
    public function deserialize(string $data, string $type, string $format = 'json'): object;
}
