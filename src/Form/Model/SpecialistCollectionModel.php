<?php

namespace App\Form\Model;

use App\Entity\Specialist;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class SpecialistCollectionModel
{
    /**
     * @var Collection<int, SpecialistModel>
     */
    private readonly Collection $specialists;

    /**
     * @param Specialist[] $specialists
     */
    public function __construct(array $specialists = [])
    {
        $this->specialists = new ArrayCollection();

        foreach ($specialists as $specialist) {
            $this->addSpecialist(SpecialistModel::fromSpecialist($specialist));
        }
    }

    /**
     * @return Collection<int, SpecialistModel>
     */
    public function getSpecialists(): Collection
    {
        return $this->specialists;
    }

    public function addSpecialist(SpecialistModel $specialist): self
    {
        if (!$this->specialists->contains($specialist)) {
            $this->specialists->add($specialist);
        }

        return $this;
    }

    public function removeSpecialist(SpecialistModel $specialist): self
    {
        $this->specialists->removeElement($specialist);

        return $this;
    }
}
