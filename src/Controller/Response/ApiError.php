<?php

namespace App\Controller\Response;

final readonly class ApiError implements \JsonSerializable
{
    /**
     * @param ApiFieldError[] $errors
     */
    public function __construct(
        private string $error,
        private array $errors = [],
    ) {
    }

    /**
     * @return array{error: string, errors?: ApiFieldError[]}
     */
    public function jsonSerialize(): array
    {
        $response = ['error' => $this->error];
        if ([] !== $this->errors) {
            $response['errors'] = $this->errors;
        }

        return $response;
    }
}
