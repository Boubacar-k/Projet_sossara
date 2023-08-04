<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CandidatureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
#[ApiResource]
class Candidature implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'candidatures')]
    private ?User $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'candidatures')]
    private ?BienImmo $bien = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_accepted = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
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

    public function isIsAccepted(): ?bool
    {
        return $this->is_accepted;
    }

    public function setIsAccepted(?bool $is_accepted): static
    {
        $this->is_accepted = $is_accepted;

        return $this;
    }

    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'bien' => $this->bien,
            'utilisateur' => $this->utilisateur,
            'is_accepted' => $this->is_accepted
        ];
    }
}
