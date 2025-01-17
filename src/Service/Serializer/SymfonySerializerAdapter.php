<?php

namespace App\Service\Serializer;

use Symfony\Component\Serializer\SerializerInterface;

final readonly class SymfonySerializerAdapter implements Adapter
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    public function deserialize(string $data, string $type, string $format = 'json'): object
    {
        return $this->serializer->deserialize($data, $type, $format);
    }
}
