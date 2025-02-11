<?php

namespace App\Controller\Response;

final class City implements \JsonSerializable
{
    public function __construct(
        private readonly string $name,
        private int $branchCount = 1,
    ) {
    }

    public function incrementBranchCount(): void
    {
        ++$this->branchCount;
    }

    public function getBranchCount(): int
    {
        return $this->branchCount;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'branchCount' => $this->branchCount,
        ];
    }
}
