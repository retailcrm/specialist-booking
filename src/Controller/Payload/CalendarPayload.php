<?php

namespace App\Controller\Payload;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class CalendarPayload
{
    private const int MAX_DAYS = 31;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $dateFrom,
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $dateTo,
        #[Assert\Positive]
        public ?int $specialistId = null,
        #[Assert\Positive]
        public ?int $specialtyId = null,
        #[Assert\Positive]
        public ?int $userId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            trim((string) ($payload['dateFrom'] ?? '')),
            trim((string) ($payload['dateTo'] ?? '')),
            self::toNullableInt($payload['specialistId'] ?? null),
            self::toNullableInt($payload['specialtyId'] ?? null),
            self::toNullableInt($payload['userId'] ?? null),
        );
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        $from = \DateTimeImmutable::createFromFormat('!Y-m-d', $this->dateFrom);
        $to = \DateTimeImmutable::createFromFormat('!Y-m-d', $this->dateTo);
        if (false === $from || false === $to) {
            return;
        }

        if ($to < $from) {
            $context->buildViolation('dateTo must not be earlier than dateFrom.')->atPath('dateTo')->addViolation();
        } elseif ($from->diff($to)->days >= self::MAX_DAYS) {
            $context->buildViolation(sprintf('Period must not exceed %d days.', self::MAX_DAYS))->atPath('dateTo')->addViolation();
        }
    }

    public function getDateFrom(): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $this->dateFrom);
        assert(false !== $date);

        return $date;
    }

    public function getDateTo(): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $this->dateTo);
        assert(false !== $date);

        return $date;
    }

    private static function toNullableInt(mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return is_numeric($value) ? (int) $value : -1;
    }
}
