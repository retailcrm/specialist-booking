<?php

namespace App\Form\Model;

use App\Entity\Specialty;

class SpecialtyCollectionModel
{
    /** @var SpecialtyModel[] */
    public array $specialties = [];

    /**
     * @param Specialty[] $specialties
     */
    public function __construct(array $specialties = [])
    {
        foreach ($specialties as $specialty) {
            $this->specialties[] = new SpecialtyModel($specialty);
        }
    }
}
