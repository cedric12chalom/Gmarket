<?php

namespace App\Entity;

use App\Enum\TypeTransport;
use App\Repository\CategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 20)]
    private ?string $typeTransport = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $commissionFixe = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $commissionPourcent = '0.00';

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $approuve = false;

    #[ORM\OneToMany(targetEntity: Produit::class, mappedBy: 'categorie')]
    private Collection $produits;

    public function __construct()
    {
        $this->produits = new ArrayCollection();
    }

    public function getProduits(): Collection
    {
        return $this->produits;
    }

    public function addProduit(Produit $produit): self
    {
        if (!$this->produits->contains($produit)) {
            $this->produits->add($produit);
            $produit->setCategorie($this);
        }
        return $this;
    }

    public function removeProduit(Produit $produit): self
    {
        if ($this->produits->removeElement($produit)) {
            if ($produit->getCategorie() === $this) {
                $produit->setCategorie(null);
            }
        }
        return $this;
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

    public function getTypeTransport(): ?string
    {
        return $this->typeTransport;
    }

    public function setTypeTransport(string $typeTransport): self
    {
        $this->typeTransport = $typeTransport;
        return $this;
    }

    public function getCommissionFixe(): ?string
    {
        return $this->commissionFixe;
    }

    public function setCommissionFixe(?string $commissionFixe): self
    {
        $this->commissionFixe = $commissionFixe;
        return $this;
    }

    public function getCommissionPourcent(): ?string
    {
        return $this->commissionPourcent;
    }

    public function setCommissionPourcent(?string $commissionPourcent): self
    {
        $this->commissionPourcent = $commissionPourcent;
        return $this;
    }

    public function isApprouve(): bool
    {
        return $this->approuve;
    }

    public function setApprouve(bool $approuve): self
    {
        $this->approuve = $approuve;
        return $this;
    }

    public function calculerCommission(float $prixProducteur): float
    {
        $fixe = (float) ($this->commissionFixe ?? 0);
        $pourcent = (float) ($this->commissionPourcent ?? 0);
        return $fixe + ($prixProducteur * $pourcent / 100);
    }
}
