<?php

namespace App\Controller\Response;

final readonly class ApiFieldError implements \JsonSerializable
{
    public function __construct(
        private string $path,
        private string $message,
    ) {
    }

    /**
     * @return array{path: string, message: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'path' => $this->path,
            'message' => $this->message,
        ];
    }
}
