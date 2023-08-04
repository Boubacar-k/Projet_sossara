<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\BienImmo;
use App\Repository\CommoditeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommoditeRepository::class)]
#[ApiResource]
class Commodite implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column]
    private ?string $icone = null;

    #[ORM\ManyToMany(targetEntity: BienImmo::class, mappedBy: 'commodites')]
    private Collection $bienImmos;

    public function __construct()
    {
        $this->bienImmos = new ArrayCollection();
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

    public function getIcone()
    {
        return $this->icone;
    }

    public function setIcone($icone): static
    {
        $this->icone = $icone;

        return $this;
    }


    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'icone' => $this->icone,
            'biens' => $this->bienImmos
        ];
    }

    /**
     * @return Collection<int, BienImmo>
     */
    public function getBienImmos(): Collection
    {
        return $this->bienImmos;
    }

    public function addBienImmo(BienImmo $bienImmo): static
    {
        if (!$this->bienImmos->contains($bienImmo)) {
            $this->bienImmos->add($bienImmo);
            $bienImmo->addCommodite($this);
        }

        return $this;
    }

    public function removeBienImmo(BienImmo $bienImmo): static
    {
        if ($this->bienImmos->removeElement($bienImmo)) {
            $bienImmo->removeCommodite($this);
        }

        return $this;
    }

}
