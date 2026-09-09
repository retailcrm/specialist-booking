<?php

namespace App\Controller\Response;

use App\Entity\Specialty;

final readonly class AdminSpecialtiesResponse implements \JsonSerializable
{
    /**
     * @param AdminSpecialty[] $specialties
     */
    public function __construct(
        private array $specialties,
    ) {
    }

    /**
     * @param Specialty[] $specialties
     */
    public static function fromSpecialties(array $specialties): self
    {
        return new self(array_map(AdminSpecialty::fromEntity(...), $specialties));
    }

    /**
     * @return array{specialties: AdminSpecialty[]}
     */
    public function jsonSerialize(): array
    {
        return ['specialties' => $this->specialties];
    }
}
