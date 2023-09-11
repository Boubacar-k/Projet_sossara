<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\FavorisRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavorisRepository::class)]
#[ApiResource]
class Favoris implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'favoris')]
    private ?User $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'favoris')]
    private ?BienImmo $bien = null;

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

    public function jsonSerialize() {
        
        return [
            'id' => $this->id,
            'utilisateurs' => $this->utilisateur,
            'bien' => $this->bien,
        ];
    }
}
