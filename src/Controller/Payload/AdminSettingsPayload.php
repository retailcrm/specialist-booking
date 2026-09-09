<?php

namespace App\Controller\Payload;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class AdminSettingsPayload
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Range(min: 15, max: 360)]
        public ?int $slotDuration = null,
        #[Assert\Type('bool')]
        public bool $chooseStore = false,
        #[Assert\Type('bool')]
        public bool $chooseCity = false,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            self::toNullableInt($payload['slotDuration'] ?? null),
            (bool) ($payload['chooseStore'] ?? false),
            (bool) ($payload['chooseCity'] ?? false),
        );
    }

    public function hasSettings(): bool
    {
        return null !== $this->slotDuration;
    }

    public function getSlotDuration(): int
    {
        if (null === $this->slotDuration) {
            throw new \LogicException('Slot duration is not defined.');
        }

        return $this->slotDuration;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if (null !== $this->slotDuration && 0 !== $this->slotDuration % 15) {
            $context
                ->buildViolation('This value should be divisible by 15.')
                ->atPath('slotDuration')
                ->addViolation()
            ;
        }
    }

    private static function toNullableInt(mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return (int) $value;
    }
}
