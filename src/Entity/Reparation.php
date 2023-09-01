<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ReparationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReparationRepository::class)]
#[ApiResource]
class Reparation implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reparations')]
    private ?BienImmo $bien = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?float $somme = null;

    #[ORM\ManyToOne(inversedBy: 'reparations')]
    private ?TypeProbleme $type = null;

    #[ORM\Column]
    private ?bool $is_ok = false;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Probleme $probleme = null;

    public function __construct(){
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBien(): ?BienImmo
    {
        return $this->bien;
    }

    public function setBien(?BienImmo $bien): static
    {
        $this->bien = $bien;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getSomme(): ?float
    {
        return $this->somme;
    }

    public function setSomme(float $somme): static
    {
        $this->somme = $somme;

        return $this;
    }

    public function getType(): ?TypeProbleme
    {
        return $this->type;
    }

    public function setType(?TypeProbleme $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isIsOk(): ?bool
    {
        return $this->is_ok;
    }

    public function setIsOk(bool $is_ok): static
    {
        $this->is_ok = $is_ok;

        return $this;
    }

    public function jsonSerialize() {
        return [
        'id' => $this->id,
        'bien' => $this->bien,
        'somme' => $this->somme,
        'probleme'=> $this->probleme,
        'createdAt' => $this->createdAt,
        'updatedAt' => $this->updatedAt,
    ];
}

    public function getProbleme(): ?Probleme
    {
        return $this->probleme;
    }

    public function setProbleme(?Probleme $probleme): static
    {
        $this->probleme = $probleme;

        return $this;
    }
}
