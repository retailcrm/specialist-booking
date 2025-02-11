<?php

namespace App\Controller\Response;

final readonly class Branch implements \JsonSerializable
{
    public function __construct(
        private string $name,
        private string $code,
        private int $specialistCount,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSpecialistCount(): int
    {
        return $this->specialistCount;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'specialistCount' => $this->specialistCount,
        ];
    }
}
