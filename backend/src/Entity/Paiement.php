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

    #[ORM\ManyToOne(targetEntity: Commande::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column(length: 50)]
    private ?string $methode = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $campayReference = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $numero = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datePaiement = null;

    public function __construct()
    {
        $this->statut = StatutPaiement::EN_ATTENTE->value;
        $this->reference = 'TL-PAY-' . strtoupper(bin2hex(random_bytes(10)));
        $this->datePaiement = new \DateTime();
    }

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

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(float|string $montant): self
    {
        $this->montant = (string) $montant;
        return $this;
    }

    public function getMethode(): ?string
    {
        return $this->methode;
    }

    public function setMethode(string $methode): self
    {
        $this->methode = $methode;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getCampayReference(): ?string
    {
        return $this->campayReference;
    }

    public function setCampayReference(?string $campayReference): self
    {
        $this->campayReference = $campayReference;
        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): self
    {
        $this->numero = $numero;
        return $this;
    }

    public function getDatePaiement(): ?\DateTimeInterface
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(?\DateTimeInterface $datePaiement): self
    {
        $this->datePaiement = $datePaiement;
        return $this;
    }

    public function valider(): void
    {
        if ($this->statut !== StatutPaiement::EN_ATTENTE->value) {
            throw new \RuntimeException('Le paiement ne peut être validé que s\'il est en attente.');
        }
        $this->statut = StatutPaiement::VALIDE->value;
        $this->datePaiement = new \DateTime();
    }

    public function echouer(): void
    {
        if ($this->statut !== StatutPaiement::EN_ATTENTE->value) {
            throw new \RuntimeException('Seul un paiement en attente peut être marqué comme échoué.');
        }
        $this->statut = StatutPaiement::ECHOUE->value;
    }

    public function rembourser(): void
    {
        if ($this->statut !== StatutPaiement::VALIDE->value) {
            throw new \RuntimeException('Seul un paiement validé peut être remboursé.');
        }
        $this->statut = StatutPaiement::REMBOURSE->value;
    }
}
