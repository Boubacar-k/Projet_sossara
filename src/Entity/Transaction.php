<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\TransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ApiResource]
class Transaction implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statut = null;

    #[ORM\Column]
    private ?float $somme = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    private ?User $utilisateur = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?BienImmo $bien = null;

    #[ORM\ManyToOne(inversedBy: 'transaction')]
    private ?TypeTransaction $typeTransaction = null;

    #[ORM\OneToMany(mappedBy: 'transaction', targetEntity: Paiement::class)]
    private Collection $paiements;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $finiAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updateAt = null;

    public function __construct()
    {
        $this->paiements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

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

    // public function getBienImmo(): ?User
    // {
    //     return $this->bien_immo;
    // }

    // public function setBienImmo(?User $bien_immo): static
    // {
    //     $this->bien_immo = $bien_immo;

    //     return $this;
    // }

    public function getBien(): ?BienImmo
    {
        return $this->bien;
    }

    public function setBien(?BienImmo $bien): static
    {
        $this->bien = $bien;

        return $this;
    }

    public function getTypeTransaction(): ?TypeTransaction
    {
        return $this->typeTransaction;
    }

    public function setTypeTransaction(?TypeTransaction $typeTransaction): static
    {
        $this->typeTransaction = $typeTransaction;

        return $this;
    }

    /**
     * @return Collection<int, Paiement>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiement $paiement): static
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setTransaction($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            // set the owning side to null (unless already changed)
            if ($paiement->getTransaction() === $this) {
                $paiement->setTransaction(null);
            }
        }

        return $this;
    }

    public function getFiniAt(): ?\DateTimeImmutable
    {
        return $this->finiAt;
    }

    public function setFiniAt(?\DateTimeImmutable $finiAt): static
    {
        $this->finiAt = $finiAt;

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

    public function getUpdateAt(): ?\DateTimeImmutable
    {
        return $this->updateAt;
    }

    public function setUpdateAt(\DateTimeImmutable $updateAt): static
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    public function jsonSerialize() {
            return [
            'id' => $this->id,
            'bien' => $this->bien,
            'statut' => $this->statut,
            'somme' => $this->somme,
            'utilisateur' => $this->utilisateur,
            'createdAt' => $this->createdAt,
            'updateAt' => $this->updateAt,
        ];
    }
}
