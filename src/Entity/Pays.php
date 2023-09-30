<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\PaysRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaysRepository::class)]
#[ApiResource]
class Pays implements \jsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 5)]
    private ?string $code_iso = null;

    #[ORM\Column(length: 5)]
    private ?string $indicatif = null;

    #[ORM\Column(length: 255,unique:true)]
    private ?string $nom = null;

    #[ORM\OneToMany(mappedBy: 'pays', targetEntity: Region::class)]
    private Collection $pays;

    public function __construct()
    {
        $this->pays = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeIso(): ?string
    {
        return $this->code_iso;
    }

    public function setCodeIso(string $code_iso): static
    {
        $this->code_iso = $code_iso;

        return $this;
    }

    public function getIndicatif(): ?string
    {
        return $this->indicatif;
    }

    public function setIndicatif(string $indicatif): static
    {
        $this->indicatif = $indicatif;

        return $this;
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
     * @return Collection<int, Region>
     */
    public function getPays(): Collection
    {
        return $this->pays;
    }

    public function addPay(Region $pay): static
    {
        if (!$this->pays->contains($pay)) {
            $this->pays->add($pay);
            $pay->setPays($this);
        }

        return $this;
    }

    public function removePay(Region $pay): static
    {
        if ($this->pays->removeElement($pay)) {
            // set the owning side to null (unless already changed)
            if ($pay->getPays() === $this) {
                $pay->setPays(null);
            }
        }

        return $this;
    }

    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'code_iso' => $this->code_iso,
            'indicatif' => $this->indicatif,
            'nom' => $this->nom
        ];
    }
}
