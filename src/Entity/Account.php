<?php

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
class Account
{
    public const string MODULE_CODE = 's-booking';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $url;

    #[ORM\Column(length: 255)]
    private string $apiKey;

    #[ORM\Column(length: 255, unique: true)]
    private string $clientId;

    /**
     * @var Collection<int, Specialist>
     */
    #[ORM\OneToMany(targetEntity: Specialist::class, mappedBy: 'account', cascade: ['all'], orphanRemoval: true)]
    private Collection $specialists;

    #[ORM\Embedded(class: AccountSettings::class, columnPrefix: 'setting_')]
    private AccountSettings $settings;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $frozen = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $simpleConnection = false;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $url,
        string $apiKey,
    ) {
        $this->url = $url;
        $this->apiKey = $apiKey;
        $this->clientId = self::MODULE_CODE . '-' . uniqid('', false);
        $this->specialists = new ArrayCollection();
        $this->settings = new AccountSettings();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public static function normalizeUrl(string $url): string
    {
        return rtrim($url, '/');
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = self::normalizeUrl($url);

        return $this;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): static
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @return Collection<int, Specialist>
     */
    public function getSpecialists(): Collection
    {
        return $this->specialists;
    }

    public function addSpecialist(Specialist $specialist): static
    {
        if (!$this->specialists->contains($specialist)) {
            $this->specialists->add($specialist);
            $specialist->setAccount($this);
        }

        return $this;
    }

    public function removeSpecialist(Specialist $specialist): static
    {
        if ($this->specialists->removeElement($specialist)) {
            // set the owning side to null (unless already changed)
            if ($specialist->getAccount() === $this) {
                $specialist->setAccount(null);
            }
        }

        return $this;
    }

    public function getSettings(): AccountSettings
    {
        return $this->settings;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function isFrozen(): ?bool
    {
        return $this->frozen;
    }

    public function setFrozen(bool $frozen): static
    {
        $this->frozen = $frozen;

        return $this;
    }

    public function isSimpleConnection(): bool
    {
        return $this->simpleConnection;
    }

    public function setSimpleConnection(bool $simpleConnection): static
    {
        $this->simpleConnection = $simpleConnection;

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
}
