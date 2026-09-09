<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private ?string $unite = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prixMin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prixMax = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'produits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Categorie $categorie = null;

    #[ORM\OneToMany(targetEntity: Lot::class, mappedBy: 'produit')]
    private Collection $lots;

    #[ORM\OneToMany(targetEntity: Variete::class, mappedBy: 'produit', cascade: ['persist'], orphanRemoval: true)]
    private Collection $varietes;

    public function __construct()
    {
        $this->lots = new ArrayCollection();
        $this->varietes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(string $unite): self
    {
        $this->unite = $unite;
        return $this;
    }

    public function getPrixMin(): ?string
    {
        return $this->prixMin;
    }

    public function setPrixMin(?string $prixMin): self
    {
        $this->prixMin = $prixMin;
        return $this;
    }

    public function getPrixMax(): ?string
    {
        return $this->prixMax;
    }

    public function setPrixMax(?string $prixMax): self
    {
        $this->prixMax = $prixMax;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;
        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getLots(): Collection
    {
        return $this->lots;
    }

    public function getVarietes(): Collection
    {
        return $this->varietes;
    }

    public function addVariete(Variete $variete): self
    {
        if (!$this->varietes->contains($variete)) {
            $this->varietes->add($variete);
            $variete->setProduit($this);
        }
        return $this;
    }

    public function removeVariete(Variete $variete): self
    {
        if ($this->varietes->removeElement($variete)) {
            if ($variete->getProduit() === $this) {
                $variete->setProduit(null);
            }
        }
        return $this;
    }

    public function validerPrix(float $prix): bool
    {
        $min = (float) ($this->prixMin ?? 0);
        $max = (float) ($this->prixMax ?? 0);
        return $prix >= $min && $prix <= $max;
    }

    public function publierProduit(): bool
    {
        return $this->categorie !== null && $this->categorie->isApprouve();
    }
}
