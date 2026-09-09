<?php

namespace App\Controller\Response;

use App\Entity\Specialty;

final readonly class AdminSpecialty implements \JsonSerializable
{
    public function __construct(
        private ?int $id,
        private string $name,
    ) {
    }

    public static function fromEntity(Specialty $specialty): self
    {
        return new self($specialty->getId(), $specialty->getName());
    }

    /**
     * @return array{id: int|null, name: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
