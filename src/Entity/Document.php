<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\DocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ApiResource]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $num_doc = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'documents')]
    private Collection $users;

    #[ORM\OneToMany(mappedBy: 'document', targetEntity: PhotoDocument::class)]
    private Collection $photoDocuments;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->photoDocuments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumDoc(): ?string
    {
        return $this->num_doc;
    }

    public function setNumDoc(?string $num_doc): static
    {
        $this->num_doc = $num_doc;

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
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addDocument($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeDocument($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, PhotoDocument>
     */
    public function getPhotoDocuments(): Collection
    {
        return $this->photoDocuments;
    }

    public function addPhotoDocument(PhotoDocument $photoDocument): static
    {
        if (!$this->photoDocuments->contains($photoDocument)) {
            $this->photoDocuments->add($photoDocument);
            $photoDocument->setDocument($this);
        }

        return $this;
    }

    public function removePhotoDocument(PhotoDocument $photoDocument): static
    {
        if ($this->photoDocuments->removeElement($photoDocument)) {
            // set the owning side to null (unless already changed)
            if ($photoDocument->getDocument() === $this) {
                $photoDocument->setDocument(null);
            }
        }

        return $this;
    }

    public function jsonSerialize() {
        $photos = [];
        foreach ($this->photoDocuments as $photoDocument) {
            $photos[] = [
                'id' => $photoDocument->getId(),
                'nom' => $photoDocument->getNom(),
            ];
        }
        return [
            'id' => $this->id,
            'num_doc' => $this->num_doc,
            'nom' => $this->nom,
            'users' => $this->users,
            'photoDocuments' => $photos
        ];
    }
}
