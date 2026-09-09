<?php

namespace App\Controller\Payload;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeletePayload
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public ?int $id = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(self::toNullableInt($payload['id'] ?? null));
    }

    public function getId(): int
    {
        if (null === $this->id) {
            throw new \LogicException('ID is not defined.');
        }

        return $this->id;
    }

    private static function toNullableInt(mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return (int) $value;
    }
}
