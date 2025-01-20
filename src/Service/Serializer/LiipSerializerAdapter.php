<?php

namespace App\Service\Serializer;

use Liip\Serializer\SerializerInterface;
use RetailCrm\Api\Factory\SerializerFactory;

final readonly class LiipSerializerAdapter implements Adapter
{
    private SerializerInterface $serializer;

    public function __construct()
    {
        $this->serializer = SerializerFactory::create();
    }

    public function deserialize(string $data, string $type, string $format = 'json'): object
    {
        return $this->serializer->deserialize($data, $type, $format);
    }
}
