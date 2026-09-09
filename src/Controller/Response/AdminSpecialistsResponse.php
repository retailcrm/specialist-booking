<?php

namespace App\Controller\Response;

use App\Entity\Specialist;
use App\Entity\Specialty;
use Gaufrette\Extras\Resolvable\ResolvableFilesystem;

final readonly class AdminSpecialistsResponse implements \JsonSerializable
{
    /**
     * @param Specialist[] $specialists
     * @param Specialty[]  $specialties
     * @param AdminStore[] $stores
     */
    public function __construct(
        private array $specialists,
        private array $specialties,
        private array $stores,
        private bool $chooseStore,
        private ResolvableFilesystem $fileSystem,
    ) {
    }

    /**
     * @return array{
     *   specialists: AdminSpecialist[],
     *   specialties: AdminSpecialty[],
     *   stores: AdminStore[],
     *   chooseStore: bool,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'specialists' => array_map(
                fn (Specialist $specialist): AdminSpecialist => AdminSpecialist::fromEntity($specialist, $this->fileSystem),
                $this->specialists,
            ),
            'specialties' => array_map(AdminSpecialty::fromEntity(...), $this->specialties),
            'stores' => $this->stores,
            'chooseStore' => $this->chooseStore,
        ];
    }
}
