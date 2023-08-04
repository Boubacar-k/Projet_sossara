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

    #[ORM\OneToMany(mappedBy: 'bienImmo', targetEntity: Notification::class)]
    private Collection $notifications;


    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Adresse $adresse = null;

    #[ORM\ManyToMany(targetEntity: Commodite::class, inversedBy: 'bienImmos')]
    #[JoinTable(name: 'bienImmos_commodites')]
    private Collection $commodites;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updateAt = null;

    #[ORM\Column(length: 255,nullable: true)]
    private ?string $nom = "nom";

    #[ORM\Column(nullable: true)]
    private ?int $vue = null;

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

    public function __construct()
    {
        $this->rdvs = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->commodites = new ArrayCollection();
        $this->photoImmos = new ArrayCollection();
        $this->candidatures = new ArrayCollection();
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

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setBienImmo($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getBienImmo() === $this) {
                $notification->setBienImmo(null);
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
        return [
            'id' => $this->id,
            'nb_piece' => $this->nb_piece,
            'surface' => $this->surface,
            'nom' => $this->nom,
            'vue' => $this->vue,
            'chambre' => $this->chambre,
            'photoImmos' => $this->photoImmos,
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
            'commodite' => $this->commodites
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

    public function getVue(): ?int
    {
        return $this->vue;
    }

    public function setVue(?int $vue): static
    {
        $this->vue = $vue;

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
}
