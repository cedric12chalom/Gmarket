<?php

namespace App\Entity;

use App\Enum\StatutAbonnement;
use App\Repository\AbonnementSouscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AbonnementSouscriptionRepository::class)]
class AbonnementSouscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'abonnements', targetEntity: Vendeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vendeur $vendeur = null;

    #[ORM\ManyToOne(inversedBy: 'souscriptions', targetEntity: Abonnement::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Abonnement $abonnement = null;

    #[ORM\Column(name: 'date_debut', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(name: 'date_fin', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $statut = StatutAbonnement::ESSAI->value;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->dateDebut = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVendeur(): ?Vendeur
    {
        return $this->vendeur;
    }

    public function setVendeur(?Vendeur $vendeur): static
    {
        $this->vendeur = $vendeur;
        return $this;
    }

    public function getAbonnement(): ?Abonnement
    {
        return $this->abonnement;
    }

    public function setAbonnement(?Abonnement $abonnement): static
    {
        $this->abonnement = $abonnement;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getStatut(): StatutAbonnement
    {
        return StatutAbonnement::tryFrom($this->statut) ?? StatutAbonnement::ESSAI;
    }

    public function setStatut(string|StatutAbonnement $statut): static
    {
        $this->statut = $statut instanceof StatutAbonnement ? $statut->value : $statut;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function estActifAu(\DateTimeImmutable $now): bool
    {
        return $now >= $this->dateDebut && $now <= $this->dateFin
            && in_array($this->getStatut(), [StatutAbonnement::ESSAI, StatutAbonnement::ACTIF], true);
    }

    public function joursRestants(\DateTimeImmutable $now): int
    {
        if ($this->dateFin === null) {
            return 0;
        }
        return max(0, (int) ceil(($this->dateFin->getTimestamp() - $now->getTimestamp()) / 86400));
    }
}