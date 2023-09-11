<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use App\Entity\Commodite;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\BienImmoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BienImmoRepository::class)]
// #[ApiResource(
//     operations: [
//         new GetCollection(),
//         new Post(validationContext: ['groups' => ['Default', 'bien_immo:create']]),
//         new Get(),
//         new Put(),
//         new Patch(),
//         new Delete(),
//     ],
//     normalizationContext: ['groups' => ['bien_immo:read']],
//     denormalizationContext: ['groups' => ['bien_immo:create', 'bien_immo:update']],
// )]
class BienImmo implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $nb_piece = null;

    #[ORM\Column(nullable: true)]
    private ?float $surface = null;

    #[ORM\Column]
    private ?float $prix = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'bienImmos')]
    private ?User $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'bien_immo')]
    private ?TypeImmo $typeImmo = null;

    #[ORM\OneToMany(mappedBy: 'bien', targetEntity: Rdv::class)]
    private Collection $rdvs;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Adresse $adresse = null;

    #[ORM\ManyToMany(targetEntity: Commodite::class, inversedBy: 'bienImmos')]
    // #[JoinTable(name: 'bienImmos_commodites')]
    private Collection $commodites;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updateAt = null;

    #[ORM\Column(length: 255,nullable: true)]
    private ?string $nom = "nom";

    #[ORM\Column(nullable: true)]
    private ?int $chambre = null;

    #[ORM\Column(nullable: true)]
    private ?int $cuisine = null;

    #[ORM\Column(nullable: true)]
    private ?int $toilette = null;

    #[ORM\OneToMany(mappedBy: 'bien', targetEntity: PhotoImmo::class)]
    private Collection $photoImmos;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\OneToMany(mappedBy: 'bien', targetEntity: Candidature::class)]
    private Collection $candidatures;

    #[ORM\Column(nullable: true)]
    private ?bool $is_rent = false;

    #[ORM\Column(nullable: true)]
    private ?bool $is_sell = false;

    #[ORM\OneToMany(mappedBy: 'bien', targetEntity: Probleme::class)]
    private Collection $problemes;

    #[ORM\OneToMany(mappedBy: 'bien', targetEntity: Reparation::class)]
    private Collection $reparations;

    #[ORM\ManyToOne(inversedBy: 'bien')]
    private ?Periode $periode = null;

    #[ORM\OneToMany(mappedBy: 'bien', targetEntity: Alerte::class)]
    private Collection $alertes;

    #[ORM\OneToMany(mappedBy: 'bien', targetEntity: Favoris::class)]
    private Collection $favoris;

    public function __construct()
    {
        $this->rdvs = new ArrayCollection();
        $this->commodites = new ArrayCollection();
        $this->photoImmos = new ArrayCollection();
        $this->candidatures = new ArrayCollection();
        $this->problemes = new ArrayCollection();
        $this->reparations = new ArrayCollection();
        $this->alertes = new ArrayCollection();
        $this->favoris = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNbPiece(): ?int
    {
        return $this->nb_piece;
    }

    public function setNbPiece(int $nb_piece): static
    {
        $this->nb_piece = $nb_piece;

        return $this;
    }

    public function getSurface(): ?float
    {
        return $this->surface;
    }

    public function setSurface(?float $surface): static
    {
        $this->surface = $surface;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getTypeImmo(): ?TypeImmo
    {
        return $this->typeImmo;
    }

    public function setTypeImmo(?TypeImmo $typeImmo): static
    {
        $this->typeImmo = $typeImmo;

        return $this;
    }

    /**
     * @return Collection<int, Rdv>
     */
    public function getRdvs(): Collection
    {
        return $this->rdvs;
    }

    public function addRdv(Rdv $rdv): static
    {
        if (!$this->rdvs->contains($rdv)) {
            $this->rdvs->add($rdv);
            $rdv->setBien($this);
        }

        return $this;
    }

    public function removeRdv(Rdv $rdv): static
    {
        if ($this->rdvs->removeElement($rdv)) {
            // set the owning side to null (unless already changed)
            if ($rdv->getBien() === $this) {
                $rdv->setBien(null);
            }
        }

        return $this;
    }

    public function getAdresse(): ?Adresse
    {
        return $this->adresse;
    }

    public function setAdresse(?Adresse $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function jsonSerialize() {
        $photos = [];
        foreach ($this->photoImmos as $photoImmo) {
            $photos[] = [
                'id' => $photoImmo->getId(),
                'nom' => $photoImmo->getNom(),
            ];
        }

        $commodites = [];
        foreach ($this->commodites as $commodite) {
            $commodites[] = [
                'id' => $commodite->getId(),
                'nom' => $commodite->getNom(),
                'icone' => $commodite->getIcone()
            ];
        }
        $favoris = [];
        foreach ($this->favoris as $favori) {
            $favoris[] = [
                'id' => $favori->getId(),
            ];
        }
        
        return [
            'id' => $this->id,
            'nb_piece' => $this->nb_piece,
            'surface' => $this->surface,
            'nom' => $this->nom,
            'periode' => $this->periode,
            'chambre' => $this->chambre,
            'photos' => $photos,
            'cuisine' => $this->cuisine,
            'toilette' => $this->toilette,
            'prix' => $this->prix,
            'description' => $this->description,
            'statut' => $this->statut,
            'utilisateur' => $this->utilisateur,
            'typeImmo' => $this->typeImmo,
            'adresse' => $this->adresse,
            'createdAt' => $this->createdAt,
            'updateAt' => $this->updateAt,
            'commodite' => $commodites,
            'favoris' => $favoris,
        ];
    }



    /**
     * @return Collection<int, Commodite>
     */
    public function getCommodites(): Collection
    {
        return $this->commodites;
    }

    public function addCommodite(Commodite $commodite): static
    {
        if (!$this->commodites->contains($commodite)) {
            $this->commodites->add($commodite);
        }

        return $this;
    }

    public function removeCommodite(Commodite $commodite): static
    {
        $this->commodites->removeElement($commodite);

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

    public function getUpdateAt(): ?\DateTimeImmutable
    {
        return $this->updateAt;
    }

    public function setUpdateAt(\DateTimeImmutable $updateAt): static
    {
        $this->updateAt = $updateAt;

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


    public function getChambre(): ?int
    {
        return $this->chambre;
    }

    public function setChambre(?int $chambre): static
    {
        $this->chambre = $chambre;

        return $this;
    }

    public function getCuisine(): ?int
    {
        return $this->cuisine;
    }

    public function setCuisine(?int $cuisine): static
    {
        $this->cuisine = $cuisine;

        return $this;
    }

    public function getToilette(): ?int
    {
        return $this->toilette;
    }

    public function setToilette(?int $toilette): static
    {
        $this->toilette = $toilette;

        return $this;
    }

    /**
     * @return Collection<int, PhotoImmo>
     */
    public function getPhotoImmos(): Collection
    {
        return $this->photoImmos;
    }

    public function addPhotoImmo(PhotoImmo $photoImmo): static
    {
        if (!$this->photoImmos->contains($photoImmo)) {
            $this->photoImmos->add($photoImmo);
            $photoImmo->setBien($this);
        }

        return $this;
    }

    public function removePhotoImmo(PhotoImmo $photoImmo): static
    {
        if ($this->photoImmos->removeElement($photoImmo)) {
            // set the owning side to null (unless already changed)
            if ($photoImmo->getBien() === $this) {
                $photoImmo->setBien(null);
            }
        }

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return Collection<int, Candidature>
     */
    public function getCandidatures(): Collection
    {
        return $this->candidatures;
    }

    public function addCandidature(Candidature $candidature): static
    {
        if (!$this->candidatures->contains($candidature)) {
            $this->candidatures->add($candidature);
            $candidature->setBien($this);
        }

        return $this;
    }

    public function removeCandidature(Candidature $candidature): static
    {
        if ($this->candidatures->removeElement($candidature)) {
            // set the owning side to null (unless already changed)
            if ($candidature->getBien() === $this) {
                $candidature->setBien(null);
            }
        }

        return $this;
    }

    public function isIsRent(): ?bool
    {
        return $this->is_rent;
    }

    public function setIsRent(bool $is_rent): static
    {
        $this->is_rent = $is_rent;

        return $this;
    }

    public function isIsSell(): ?bool
    {
        return $this->is_sell;
    }

    public function setIsSell(?bool $is_sell): static
    {
        $this->is_sell = $is_sell;

        return $this;
    }

    /**
     * @return Collection<int, Probleme>
     */
    public function getProblemes(): Collection
    {
        return $this->problemes;
    }

    public function addProbleme(Probleme $probleme): static
    {
        if (!$this->problemes->contains($probleme)) {
            $this->problemes->add($probleme);
            $probleme->setBien($this);
        }

        return $this;
    }

    public function removeProbleme(Probleme $probleme): static
    {
        if ($this->problemes->removeElement($probleme)) {
            // set the owning side to null (unless already changed)
            if ($probleme->getBien() === $this) {
                $probleme->setBien(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reparation>
     */
    public function getReparations(): Collection
    {
        return $this->reparations;
    }

    public function addReparation(Reparation $reparation): static
    {
        if (!$this->reparations->contains($reparation)) {
            $this->reparations->add($reparation);
            $reparation->setBien($this);
        }

        return $this;
    }

    public function removeReparation(Reparation $reparation): static
    {
        if ($this->reparations->removeElement($reparation)) {
            // set the owning side to null (unless already changed)
            if ($reparation->getBien() === $this) {
                $reparation->setBien(null);
            }
        }

        return $this;
    }

    public function getPeriode(): ?Periode
    {
        return $this->periode;
    }

    public function setPeriode(?Periode $periode): static
    {
        $this->periode = $periode;

        return $this;
    }

    /**
     * @return Collection<int, Alerte>
     */
    public function getAlertes(): Collection
    {
        return $this->alertes;
    }

    public function addAlerte(Alerte $alerte): static
    {
        if (!$this->alertes->contains($alerte)) {
            $this->alertes->add($alerte);
            $alerte->setBien($this);
        }

        return $this;
    }

    public function removeAlerte(Alerte $alerte): static
    {
        if ($this->alertes->removeElement($alerte)) {
            // set the owning side to null (unless already changed)
            if ($alerte->getBien() === $this) {
                $alerte->setBien(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Favoris>
     */
    public function getFavoris(): Collection
    {
        return $this->favoris;
    }

    public function addFavori(Favoris $favori): static
    {
        if (!$this->favoris->contains($favori)) {
            $this->favoris->add($favori);
            $favori->setBien($this);
        }

        return $this;
    }

    public function removeFavori(Favoris $favori): static
    {
        if ($this->favoris->removeElement($favori)) {
            // set the owning side to null (unless already changed)
            if ($favori->getBien() === $this) {
                $favori->setBien(null);
            }
        }

        return $this;
    }
}
