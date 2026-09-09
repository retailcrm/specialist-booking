<?php

namespace App\Controller\Response;

use App\Entity\Specialist;
use Gaufrette\Extras\Resolvable\ResolvableFilesystem;

final readonly class AdminSpecialist implements \JsonSerializable
{
    public function __construct(
        private ?int $id,
        private string $name,
        private ?int $specialtyId,
        private ?string $storeCode,
        private ?int $ordering,
        private ?string $photo,
        private ?string $photoUrl,
        /** @var array<int, array<array{string, string}>>|null */
        private ?array $workTimes,
        /** @var array<array{string, string}>|null */
        private ?array $nonWorkingDays,
    ) {
    }

    public static function fromEntity(Specialist $specialist, ResolvableFilesystem $fileSystem): self
    {
        return new self(
            $specialist->getId(),
            $specialist->getName(),
            $specialist->getSpecialty()?->getId(),
            $specialist->getStoreCode(),
            $specialist->getOrdering(),
            $specialist->getPhoto(),
            self::resolvePhoto($specialist->getPhoto(), $fileSystem),
            $specialist->getWorkTimes(),
            $specialist->getNonWorkingDays(),
        );
    }

    private static function resolvePhoto(?string $photo, ResolvableFilesystem $fileSystem): ?string
    {
        if (null === $photo) {
            return null;
        }

        return str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')
            ? $photo
            : $fileSystem->resolve($photo);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'specialtyId' => $this->specialtyId,
            'storeCode' => $this->storeCode,
            'ordering' => $this->ordering,
            'photo' => $this->photo,
            'photoUrl' => $this->photoUrl,
            // ключи дней недели — строки: пустой список иначе уехал бы массивом
            'workTimes' => null === $this->workTimes ? null : (object) $this->workTimes,
            'nonWorkingDays' => $this->nonWorkingDays,
        ];
    }
}
