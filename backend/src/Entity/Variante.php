<?php

namespace App\Entity;

use App\Repository\VarianteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VarianteRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_produit_couleur_taille', columns: ['produit_id', 'couleur', 'taille'])]
class Variante
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'variantes', targetEntity: Produit::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Produit $produit = null;

    #[ORM\Column(length: 60)]
    #[Assert\NotBlank]
    private ?string $couleur = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank]
    private ?string $taille = null;

    #[ORM\Column]
    #[Assert\GreaterThanOrEqual(0)]
    private int $stock = 0;

    #[ORM\Column(name: 'stock_vendu', options: ['default' => 0])]
    private int $stockVendu = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;
        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;
        return $this;
    }

    public function getTaille(): ?string
    {
        return $this->taille;
    }

    public function setTaille(string $taille): static
    {
        $this->taille = $taille;
        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = max(0, $stock);
        return $this;
    }

    public function getStockVendu(): int
    {
        return $this->stockVendu;
    }

    public function setStockVendu(int $stockVendu): static
    {
        $this->stockVendu = max(0, $stockVendu);
        return $this;
    }

    /**
     * Décrémente le stock et incrémente le compteur de ventes.
     *
     * @throws \DomainException si le stock est insuffisant.
     */
    public function vendre(int $quantite): void
    {
        if ($quantite <= 0) {
            throw new \DomainException('Quantité invalide.');
        }
        if ($this->stock < $quantite) {
            throw new \DomainException(sprintf('Stock insuffisant pour %s / %s (reste %d).', $this->couleur, $this->taille, $this->stock));
        }
        $this->stock -= $quantite;
        $this->stockVendu += $quantite;
    }
}