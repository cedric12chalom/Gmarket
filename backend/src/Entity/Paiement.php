<?php

namespace App\Entity;

use App\Enum\StatutPaiement;
use App\Repository\PaiementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'paiement', targetEntity: Commande::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    #[ORM\Column]
    private float $montant = 0.0;

    #[ORM\Column]
    private float $tauxCommission = 0.0;

    #[ORM\Column]
    private float $commission = 0.0;

    #[ORM\Column(length: 30)]
    private string $statut = StatutPaiement::EN_ATTENTE->value;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): static
    {
        $this->commande = $commande;
        return $this;
    }

    public function getMontant(): float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): static
    {
        $this->montant = round($montant, 2);
        return $this;
    }

    public function getTauxCommission(): float
    {
        return $this->tauxCommission;
    }

    public function setTauxCommission(float $tauxCommission): static
    {
        $this->tauxCommission = $tauxCommission;
        return $this;
    }

    public function getCommission(): float
    {
        return $this->commission;
    }

    public function setCommission(float $commission): static
    {
        $this->commission = round($commission, 2);
        return $this;
    }

    public function getStatut(): StatutPaiement
    {
        return StatutPaiement::tryFrom($this->statut) ?? StatutPaiement::EN_ATTENTE;
    }

    public function setStatut(string|StatutPaiement $statut): static
    {
        $this->statut = $statut instanceof StatutPaiement ? $statut->value : $statut;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}