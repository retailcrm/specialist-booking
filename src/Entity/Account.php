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

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $locale = null;

    public function __construct(
        string $url,
        string $apiKey,
    ) {
        $this->url = $url;
        $this->apiKey = $apiKey;
        $this->clientId = self::MODULE_CODE . '-' . uniqid('', false);
        $this->specialists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

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

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getRequiredLocale(): string
    {
        return $this->locale ?? 'en_GB';
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = null === $locale ? null : mb_strtolower($locale);

        return $this;
    }
}
