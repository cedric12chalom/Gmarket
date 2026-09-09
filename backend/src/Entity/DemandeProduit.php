<?php

namespace App\Entity;

use App\Enum\StatutDemande;
use App\Repository\DemandeProduitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DemandeProduitRepository::class)]
class DemandeProduit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Producteur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Producteur $producteur = null;

    #[ORM\Column(length: 255)]
    private ?string $nomProduit = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $categorieSuggeree = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $unite = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motifRefus = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateTraitement = null;

    public function __construct()
    {
        $this->statut = StatutDemande::EN_ATTENTE->value;
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getProducteur(): ?Producteur { return $this->producteur; }
    public function setProducteur(?Producteur $producteur): self { $this->producteur = $producteur; return $this; }
    public function getNomProduit(): ?string { return $this->nomProduit; }
    public function setNomProduit(string $nomProduit): self { $this->nomProduit = $nomProduit; return $this; }
    public function getCategorieSuggeree(): ?string { return $this->categorieSuggeree; }
    public function setCategorieSuggeree(?string $categorieSuggeree): self { $this->categorieSuggeree = $categorieSuggeree; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getUnite(): ?string { return $this->unite; }
    public function setUnite(?string $unite): self { $this->unite = $unite; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(?string $statut): self { $this->statut = $statut; return $this; }
    public function getMotifRefus(): ?string { return $this->motifRefus; }
    public function setMotifRefus(?string $motifRefus): self { $this->motifRefus = $motifRefus; return $this; }
    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(?\DateTimeInterface $dateCreation): self { $this->dateCreation = $dateCreation; return $this; }
    public function getDateTraitement(): ?\DateTimeInterface { return $this->dateTraitement; }
    public function setDateTraitement(?\DateTimeInterface $dateTraitement): self { $this->dateTraitement = $dateTraitement; return $this; }
}
