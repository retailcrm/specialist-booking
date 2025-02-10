<?php

namespace App\Entity;

use App\Repository\SpecialistRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SpecialistRepository::class)]
#[ORM\Index(name: 'specialist_account_id_ordering_idx', columns: ['account_id', 'ordering'])]
class Specialist
{
    public const string CUSTOM_DICTIONARY_ELEMENT_CODE_PREFIX = 's-';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column]
    private int $ordering = 99;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\ManyToOne(inversedBy: 'specialists')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Specialty $specialty = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $storeCode = null;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->createdAt = new \DateTimeImmutable();
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

    public static function getIdFromDictionaryElementCode(string $code): ?int
    {
        if (!preg_match('/^' . preg_quote(self::CUSTOM_DICTIONARY_ELEMENT_CODE_PREFIX, '/') . '(\d+)$/', $code, $matches)) {
            return null;
        }

        return (int) $matches[1];
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getSpecialty(): ?Specialty
    {
        return $this->specialty;
    }

    public function setSpecialty(?Specialty $specialty): static
    {
        $this->specialty = $specialty;

        return $this;
    }

    public function getStoreCode(): ?string
    {
        return $this->storeCode;
    }

    public function setStoreCode(?string $storeCode): static
    {
        $this->storeCode = $storeCode;

        return $this;
    }
}
