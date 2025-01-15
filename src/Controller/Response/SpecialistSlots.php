<?php

namespace App\Controller\Response;

use App\Entity\Specialist;
use App\Service\DTO\DaySlots;

final readonly class SpecialistSlots implements \JsonSerializable
{
    /**
     * @param DaySlots[] $slots
     */
    public function __construct(
        private Specialist $specialist,
        private array $slots,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): mixed
    {
        $slots = [];
        foreach ($this->slots as $daySlots) {
            $slots[$daySlots->getDate()] = array_map(
                fn (\DateTimeImmutable $slot): string => $slot->format('H:i'),
                $daySlots->getSlots()
            );
        }

        return [
            'specialist_id' => $this->specialist->getDictionaryElementCode(),
            'slots' => $slots,
        ];
    }
}
