<?php

namespace App\Controller\Response;

final readonly class AdminStore implements \JsonSerializable
{
    public function __construct(
        public string $code,
        public string $name,
    ) {
    }

    /**
     * @return array{code: string, name: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
        ];
    }
}
