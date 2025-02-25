<?php

namespace App\Controller\Response;

use App\Service\DTO\DaySlots;

final readonly class Specialist implements \JsonSerializable
{
    public function __construct(
        private string $id,
        private string $name,
        private ?string $position,
        private ?string $photo,
        private DaySlots $nearestSlots,
    ) {
    }

    public static function fromEntity(
        \App\Entity\Specialist $specialist,
        DaySlots $daySlots,
        ?string $photo,
    ): self {
        return new self(
            $specialist->getDictionaryElementCode(),
            $specialist->getName(),
            $specialist->getSpecialty()?->getName(),
            $photo,
            $daySlots
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'photo' => $this->photo,
            'nearestSlots' => [
                'date' => $this->nearestSlots->getDate(),
                'slots' => array_map(
                    fn (\DateTimeImmutable $slot): string => $slot->format('H:i'),
                    $this->nearestSlots->getSlots()
                ),
            ],
        ];
    }
}
