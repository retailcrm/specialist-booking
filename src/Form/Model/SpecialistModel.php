<?php

namespace App\Form\Model;

use App\Entity\Specialist;
use App\Entity\Specialty;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class SpecialistModel
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $name;

    public ?Specialty $specialty = null;

    #[Assert\Range(min: 0, max: 9999)]
    public int $ordering = 99;

    public ?string $photo = null;

    public ?UploadedFile $photoFile = null;

    public ?string $storeCode = null;

    public function __construct(public ?int $id = null)
    {
    }

    public static function fromSpecialist(Specialist $s): self
    {
        $specialistModel = new self($s->getId());
        $specialistModel->name = $s->getName();
        $specialistModel->specialty = $s->getSpecialty();
        $specialistModel->ordering = $s->getOrdering() ?? 99;
        $specialistModel->photo = $s->getPhoto();
        $specialistModel->storeCode = $s->getStoreCode();

        return $specialistModel;
    }
}
