<?php

namespace App\Entity;

use App\Repository\LigneCommandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LigneCommandeRepository::class)]
class LigneCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    #[ORM\ManyToOne(targetEntity: Lot::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lot $lot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $quantite = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prixUnitaire = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $sousTotal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): self
    {
        $this->commande = $commande;
        return $this;
    }

    public function getLot(): ?Lot
    {
        return $this->lot;
    }

    public function setLot(?Lot $lot): self
    {
        $this->lot = $lot;
        return $this;
    }

    public function getQuantite(): float
    {
        return (float) $this->quantite;
    }

    public function setQuantite(float|string $quantite): self
    {
        $this->quantite = (string) $quantite;
        return $this;
    }

    public function getPrixUnitaire(): float
    {
        return (float) $this->prixUnitaire;
    }

    public function setPrixUnitaire(float|string $prixUnitaire): self
    {
        $this->prixUnitaire = (string) $prixUnitaire;
        return $this;
    }

    public function getSousTotal(): float
    {
        return (float) $this->sousTotal;
    }

    public function setSousTotal(float|string $sousTotal): self
    {
        $this->sousTotal = (string) $sousTotal;
        return $this;
    }

    public function calculerSousTotal(): float
    {
        return $this->getQuantite() * $this->getPrixUnitaire();
    }
}
