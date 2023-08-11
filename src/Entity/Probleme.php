<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ProblemeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProblemeRepository::class)]
// #[ApiResource]
class Probleme implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\ManyToOne(inversedBy: 'problemes')]
    private ?User $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'problemes')]
    private ?BienImmo $bien = null;

    #[ORM\ManyToOne(inversedBy: 'probleme')]
    private ?TypeProbleme $typeProbleme = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'probleme', targetEntity: PhotoReclamation::class)]
    private Collection $photoReclamations;

    #[ORM\Column(nullable: true)]
    private ?bool $is_ok = false;

    public function __construct(){
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->photoReclamations = new ArrayCollection();
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

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

    public function getBien(): ?BienImmo
    {
        return $this->bien;
    }

    public function setBien(?BienImmo $bien): static
    {
        $this->bien = $bien;

        return $this;
    }

    public function getTypeProbleme(): ?TypeProbleme
    {
        return $this->typeProbleme;
    }

    public function setTypeProbleme(?TypeProbleme $typeProbleme): static
    {
        $this->typeProbleme = $typeProbleme;

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

    /**
     * @return Collection<int, PhotoReclamation>
     */
    public function getPhotoReclamations(): Collection
    {
        return $this->photoReclamations;
    }

    public function addPhotoReclamation(PhotoReclamation $photoReclamation): static
    {
        if (!$this->photoReclamations->contains($photoReclamation)) {
            $this->photoReclamations->add($photoReclamation);
            $photoReclamation->setProbleme($this);
        }

        return $this;
    }

    public function removePhotoReclamation(PhotoReclamation $photoReclamation): static
    {
        if ($this->photoReclamations->removeElement($photoReclamation)) {
            // set the owning side to null (unless already changed)
            if ($photoReclamation->getProbleme() === $this) {
                $photoReclamation->setProbleme(null);
            }
        }

        return $this;
    }

    public function jsonSerialize() {
        $photos = [];
            foreach ($this->photoReclamations as $photoReclamation) {
                $photos[] = [
                    'id' => $photoReclamation->getId(),
                    'nom' => $photoReclamation->getNom(),
                ];
            }
        return [
            'id' => $this->id,
            'contenu' => $this->contenu,
            'createdAt' => $this->createdAt,
            'updateAt' => $this->updatedAt,
            'bien' => $this->bien,
            'photoReclamations'=> $photos,
            'utilisateur' => $this->utilisateur
        ];
    }

    public function isIsOk(): ?bool
    {
        return $this->is_ok;
    }

    public function setIsOk(?bool $is_ok): static
    {
        $this->is_ok = $is_ok;

        return $this;
    }
}
