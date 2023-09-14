<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use App\State\UserPasswordHasher;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[ApiResource]
class User implements UserInterface, PasswordAuthenticatedUserInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    // #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    // #[Groups(['user:read', 'user:create', 'user:update'])]
    private ?string $nom = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    // #[Groups(['user:read', 'user:create', 'user:update'])]
    protected ?string $email = null;

    // #[ORM\Column(type: 'json')]
    // private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\NotBlank]
    // #[Groups(['user:create', 'user:update'])]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    // #[Groups(['user:read', 'user:create', 'user:update'])]
    private $isVerified = false;

    #[ORM\Column(nullable: true,type: Types::DATE_MUTABLE)]
    // #[Groups(['user:read', 'user:create', 'user:update'])]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(length: 15, unique: true)]
    // #[Groups(['user:read', 'user:create', 'user:update'])]
    private ?string $telephone = "";

    #[ORM\Column]
    // #[Groups(['user:read', 'user:create', 'user:update'])]
    private ?bool $is_certified = null;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: BienImmo::class)]
    // #[Groups(['user:read'])]
    private Collection $bienImmos;

    #[ORM\OneToMany(mappedBy: 'bien_immo', targetEntity: Transaction::class)]
    // #[Groups(['user:read'])]
    private Collection $transactions;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Paiement::class)]
    // #[Groups(['user:read'])]
    private Collection $paiements;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Rdv::class)]
    // #[Groups(['user:read'])]
    private Collection $rdvs;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Commentaire::class)]
    // #[Groups(['user:read'])]
    private Collection $commentaires;

    #[ORM\Column(length: 255, nullable: true)]
    // #[Groups(['user:read', 'user:create', 'user:update'])]
    private ?string $photo = null;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'parent')]
    private Collection $children;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'children')]
    private User|null $parent = null;
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updateAt = null;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Message::class, orphanRemoval: true)]
    private Collection $messages;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Participant::class, orphanRemoval: true)]
    private Collection $participants;

    #[ORM\ManyToMany(targetEntity: Document::class, inversedBy: 'users')]
    private Collection $documents;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Candidature::class)]
    private Collection $candidatures;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Probleme::class)]
    private Collection $problemes;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Alerte::class)]
    private Collection $alertes;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Blog::class)]
    private Collection $blogs;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Favoris::class)]
    private Collection $favoris;


    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deleteAt = null;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: UserAdresse::class)]
    private Collection $userAdresses;

    #[ORM\ManyToMany(targetEntity: Role::class, mappedBy: 'users')]
    private Collection $roles;


    public function __construct()
    {
        $this->bienImmos = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->paiements = new ArrayCollection();
        $this->rdvs = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->candidatures = new ArrayCollection();
        $this->problemes = new ArrayCollection();
        $this->alertes = new ArrayCollection();
        $this->blogs = new ArrayCollection();
        $this->favoris = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->userAdresses = new ArrayCollection();
        $this->roles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roleNames = $this->roles->map(fn ($role) => $role->getName())->toArray();

        $roleNames[] = 'ROLE_USER';

        return array_unique($roleNames);
    }

    // public function setRoles(array $roles): static
    // {
    //     $this->roles = $roles;

    //     return $this;
    // }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
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
            $bienImmo->setUtilisateur($this);
        }

        return $this;
    }

    public function removeBienImmo(BienImmo $bienImmo): static
    {
        if ($this->bienImmos->removeElement($bienImmo)) {
            // set the owning side to null (unless already changed)
            if ($bienImmo->getUtilisateur() === $this) {
                $bienImmo->setUtilisateur(null);
            }
        }

        return $this;
    }

    
    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            // $transaction->setBien($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getBien() === $this) {
                $transaction->setBien(null);
            }
        }

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
            $paiement->setUtilisateur($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            // set the owning side to null (unless already changed)
            if ($paiement->getUtilisateur() === $this) {
                $paiement->setUtilisateur(null);
            }
        }

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
            $rdv->setUtilisateur($this);
        }

        return $this;
    }

    public function removeRdv(Rdv $rdv): static
    {
        if ($this->rdvs->removeElement($rdv)) {
            // set the owning side to null (unless already changed)
            if ($rdv->getUtilisateur() === $this) {
                $rdv->setUtilisateur(null);
            }
        }

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

    public function isIsCertified(): ?bool
    {
        return $this->is_certified;
    }

    public function setIsCertified(bool $is_certified): static
    {
        $this->is_certified = $is_certified;

        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setUtilisateur($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getUtilisateur() === $this) {
                $commentaire->setUtilisateur(null);
            }
        }

        return $this;
    }

    // public function isIsVerified(): ?bool
    // {
    //     return $this->isVerified;
    // }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function jsonSerialize() {
        $favoris = [];
        foreach ($this->favoris as $favori) {
            $favoris[] = [
                'id' => $favori->getId(),
            ];
        }
        $children = [];
        foreach ($this->children as $child) {
            $children[] = [
                'id' => $child->getId(),
                // Autres propriétés de l'enfant à inclure si nécessaire
            ];
        }
        $adresse = [];
        foreach ($this->userAdresses as $add) {
            $adresse[] = [
                'id' => $add->getId(),
                'nom' => $add->getQuartier(),
            ];
        }
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'email' => $this->email,
            // 'roles' => $this->roles,
            'telephone' => $this->telephone,
            'dateNaissance' => $this->dateNaissance,
            'photo' => $this->photo,
            'is_certified' => $this->is_certified,
            'favoris' => $favoris,
            'agent' => $children,
            'parent' => $this->parent ? $this->parent->getId() : null,
            'adresse' => $adresse
        ];
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

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setUtilisateur($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getUtilisateur() === $this) {
                $message->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setUtilisateur($this);
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): static
    {
        if ($this->participants->removeElement($participant)) {
            // set the owning side to null (unless already changed)
            if ($participant->getUtilisateur() === $this) {
                $participant->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        $this->documents->removeElement($document);

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
            $candidature->setUtilisateur($this);
        }

        return $this;
    }

    public function removeCandidature(Candidature $candidature): static
    {
        if ($this->candidatures->removeElement($candidature)) {
            // set the owning side to null (unless already changed)
            if ($candidature->getUtilisateur() === $this) {
                $candidature->setUtilisateur(null);
            }
        }

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
            $probleme->setUtilisateur($this);
        }

        return $this;
    }

    public function removeProbleme(Probleme $probleme): static
    {
        if ($this->problemes->removeElement($probleme)) {
            // set the owning side to null (unless already changed)
            if ($probleme->getUtilisateur() === $this) {
                $probleme->setUtilisateur(null);
            }
        }

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
            $alerte->setUtilisateur($this);
        }

        return $this;
    }

    public function removeAlerte(Alerte $alerte): static
    {
        if ($this->alertes->removeElement($alerte)) {
            // set the owning side to null (unless already changed)
            if ($alerte->getUtilisateur() === $this) {
                $alerte->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Blog>
     */
    public function getBlogs(): Collection
    {
        return $this->blogs;
    }

    public function addBlog(Blog $blog): static
    {
        if (!$this->blogs->contains($blog)) {
            $this->blogs->add($blog);
            $blog->setUtilisateur($this);
        }

        return $this;
    }

    public function removeBlog(Blog $blog): static
    {
        if ($this->blogs->removeElement($blog)) {
            // set the owning side to null (unless already changed)
            if ($blog->getUtilisateur() === $this) {
                $blog->setUtilisateur(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->email;
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
            $favori->setUtilisateur($this);
        }

        return $this;
    }

    public function removeFavori(Favoris $favori): static
    {
        if ($this->favoris->removeElement($favori)) {
            // set the owning side to null (unless already changed)
            if ($favori->getUtilisateur() === $this) {
                $favori->setUtilisateur(null);
            }
        }

        return $this;
    }

    public function getParent(): ?User
    {
        return $this->parent;
    }

    public function setParent(?User $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren()
    {
        
        $children = [];
        foreach ($this->children as $child) {
            $children[] = [
                'id' => $child->getId(),
                'nom' => $child->getNom(),
                'email' => $child->getEmail(),
                'roles' => $child->getRoles(),
                'telephone' => $child->getTelephone(),
                'dateNaissance' => $child->getDateNaissance(),
                'photo' => $child->getPhoto(),
                'is_certified' => $child->isIsCertified(),
                'adresse' => $child->getUserAdresses()
            ];
        }
        return $children;
    }

    public function addChild(User $child)
    {
        $this->children[] = $child;
        $child->setParent($this);
    }

    public function getDeleteAt(): ?\DateTimeImmutable
    {
        return $this->deleteAt;
    }

    public function setDeleteAt(?\DateTimeImmutable $deleteAt): static
    {
        $this->deleteAt = $deleteAt;

        return $this;
    }

    /**
     * @return Collection<int, UserAdresse>
     */
    public function getUserAdresses()
    {
        $adresse = [];
        foreach ($this->userAdresses as $add) {
            $adresse[] = [
                'id' => $add->getId(),
                'nom' => $add->getQuartier(),
            ];
        }
        return $adresse;
    }

    public function addUserAdress(UserAdresse $userAdress): static
    {
        if (!$this->userAdresses->contains($userAdress)) {
            $this->userAdresses->add($userAdress);
            $userAdress->setUtilisateur($this);
        }

        return $this;
    }

    public function removeUserAdress(UserAdresse $userAdress): static
    {
        if ($this->userAdresses->removeElement($userAdress)) {
            // set the owning side to null (unless already changed)
            if ($userAdress->getUtilisateur() === $this) {
                $userAdress->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Role>
     */
    // public function getRoles(): Collection
    // {
    //     return $this->roles;
    // }

    public function addRole(Role $role): static
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
            $role->addUser($this);
        }

        return $this;
    }

    public function removeRole(Role $role): static
    {
        if ($this->roles->removeElement($role)) {
            $role->removeUser($this);
        }

        return $this;
    }

}
