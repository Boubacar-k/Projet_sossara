<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\TypeImmoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeImmoRepository::class)]
#[ApiResource]
class TypeImmo implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\OneToMany(mappedBy: 'typeImmo', targetEntity: BienImmo::class)]
    private Collection $bien_immo;

    public function __construct()
    {
        $this->bien_immo = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * @return Collection<int, BienImmo>
     */
    public function getBienImmo(): Collection
    {
        return $this->bien_immo;
    }

    public function addBienImmo(BienImmo $bienImmo): static
    {
        if (!$this->bien_immo->contains($bienImmo)) {
            $this->bien_immo->add($bienImmo);
            $bienImmo->setTypeImmo($this);
        }

        return $this;
    }

    public function removeBienImmo(BienImmo $bienImmo): static
    {
        if ($this->bien_immo->removeElement($bienImmo)) {
            // set the owning side to null (unless already changed)
            if ($bienImmo->getTypeImmo() === $this) {
                $bienImmo->setTypeImmo(null);
            }
        }

        return $this;
    }

    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
        ];
    }
}
