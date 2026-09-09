<?php

namespace App\Controller\Payload;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SpecialtyPayload
{
    public function __construct(
        #[Assert\Positive]
        public ?int $id,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            self::toNullableInt($payload['id'] ?? null),
            trim((string) ($payload['name'] ?? '')),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    private static function toNullableInt(mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return (int) $value;
    }
}
