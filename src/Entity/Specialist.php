<?php

namespace App\Entity;

use App\Repository\SpecialistRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SpecialistRepository::class)]
class Specialist
{
    public const string CUSTOM_DICTIONARY_ELEMENT_CODE_PREFIX = 's-';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $position = null;

    #[ORM\Column]
    private int $ordering = 99;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\ManyToOne(inversedBy: 'specialists')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getDictionaryElementCode(): string
    {
        if (null === $this->getId()) {
            throw new \RuntimeException('Specialist ID is not set');
        }

        return self::CUSTOM_DICTIONARY_ELEMENT_CODE_PREFIX . $this->getId();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getOrdering(): ?int
    {
        return $this->ordering;
    }

    public function setOrdering(int $ordering): static
    {
        $this->ordering = $ordering;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): static
    {
        $this->account = $account;

        return $this;
    }
}
