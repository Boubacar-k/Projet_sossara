<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserAdresseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserAdresseRepository::class)]
#[ApiResource]
class UserAdresse implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $quartier = null;

    #[ORM\ManyToOne(inversedBy: 'userAdresses')]
    private ?User $utilisateur = null;

    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuartier(): ?string
    {
        return $this->quartier;
    }

    public function setQuartier(string $quartier): static
    {
        $this->quartier = $quartier;

        return $this;
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

    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'quartier' => $this->getQuartier(),
            'utilisateur' => $this->getUtilisateur(),
        ];
    }

}
