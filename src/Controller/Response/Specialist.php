<?php

namespace App\Controller\Response;

use App\Service\CustomFieldManager;

class Specialist implements \JsonSerializable
{
    private const array SLOTS = [
        ['12:00', '13:00', '15:00', '18:00'],
        ['15:00', '17:00', '18:00'],
        ['13:00', '14:00', '15:00', '17:00'],
        ['12:00', '15:00', '16:00', '19:00'],
    ];

    /** @var array{date: string, slots: string[]} */
    private array $nearestSlots = [
        'date' => '2024-01-16',
        'slots' => [],
    ];

    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly ?string $position,
        private readonly ?string $photo,
    ) {
        $this->nearestSlots['slots'] = self::SLOTS[array_rand(self::SLOTS)];
    }

    public static function fromEntity(\App\Entity\Specialist $specialist, string $specialistsUploadBaseUrl): self
    {
        return new self(
            CustomFieldManager::ELEMENT_CODE_PREFIX . $specialist->getId(),
            $specialist->getName(),
            $specialist->getPosition(),
            $specialistsUploadBaseUrl . '/' . $specialist->getPhoto(),
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
            'nearestSlots' => $this->nearestSlots,
        ];
    }
}
