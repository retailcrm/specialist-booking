<?php

namespace App\Form\Model;

use App\Entity\Specialty;
use Symfony\Component\Validator\Constraints as Assert;

class SpecialtyModel
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name;

    public function __construct(?Specialty $specialty = null)
    {
        if ($specialty) {
            $this->name = $specialty->getName();
        }
    }

    public function updateEntity(Specialty $specialty): void
    {
        $specialty->setName($this->name);
    }
}
