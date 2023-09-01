<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\PeriodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PeriodeRepository::class)]
#[ApiResource]
class Periode implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\OneToMany(mappedBy: 'periode', targetEntity: BienImmo::class)]
    private Collection $bien;

    public function __construct()
    {
        $this->bien = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    /**
     * @return Collection<int, BienImmo>
     */
    public function getBien(): Collection
    {
        return $this->bien;
    }

    public function addBien(BienImmo $bien): static
    {
        if (!$this->bien->contains($bien)) {
            $this->bien->add($bien);
            $bien->setPeriode($this);
        }

        return $this;
    }

    public function removeBien(BienImmo $bien): static
    {
        if ($this->bien->removeElement($bien)) {
            // set the owning side to null (unless already changed)
            if ($bien->getPeriode() === $this) {
                $bien->setPeriode(null);
            }
        }

        return $this;
    }

    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'nom' => $this->titre
        ];
    }
}
