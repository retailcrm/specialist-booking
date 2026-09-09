<?php

namespace App\Controller\Payload;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class BookPayload
{
    public function __construct(
        #[Assert\Positive]
        public int $specialistId,
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date,
        #[Assert\NotBlank]
        #[Assert\Regex('/^\d{2}:\d{2}$/')]
        public string $time,
        #[Assert\Positive]
        public ?int $customerId,
        #[Assert\Length(max: 255)]
        public string $firstName,
        #[Assert\Length(max: 255)]
        public ?string $lastName,
        #[Assert\Length(max: 32)]
        public string $phone,
        #[Assert\Length(max: 1000)]
        public ?string $comment,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_numeric($payload['specialistId'] ?? null) ? (int) $payload['specialistId'] : 0,
            trim((string) ($payload['date'] ?? '')),
            trim((string) ($payload['time'] ?? '')),
            is_numeric($payload['customerId'] ?? null) ? (int) $payload['customerId'] : null,
            trim((string) ($payload['firstName'] ?? '')),
            self::toNullableString($payload['lastName'] ?? null),
            trim((string) ($payload['phone'] ?? '')),
            self::toNullableString($payload['comment'] ?? null),
        );
    }

    /** Имя и телефон обязательны только без привязки: у клиента CRM они уже в карточке. */
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if (null !== $this->customerId) {
            return;
        }

        if ('' === $this->firstName) {
            $context->buildViolation('First name is required.')->atPath('firstName')->addViolation();
        }
        if (mb_strlen($this->phone) < 5) {
            $context->buildViolation('Phone is required.')->atPath('phone')->addViolation();
        }
    }

    public function getDateTime(): ?\DateTimeImmutable
    {
        $dateTime = \DateTimeImmutable::createFromFormat('!Y-m-d H:i', $this->date . ' ' . $this->time);

        return false === $dateTime ? null : $dateTime;
    }

    private static function toNullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return '' === $value ? null : $value;
    }
}
