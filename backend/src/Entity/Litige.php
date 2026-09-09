<?php

namespace App\Entity;

use App\Enum\StatutLitige;
use App\Repository\LitigeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LitigeRepository::class)]
class Litige
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Commande::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    #[ORM\ManyToOne(targetEntity: Administrateur::class, inversedBy: 'litiges')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Administrateur $adminTraiteur = null;

    #[ORM\Column(length: 255)]
    private ?string $motif = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resolution = null;

    public function __construct()
    {
        $this->statut = StatutLitige::OUVERT->value;
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function getAdminTraiteur(): ?Administrateur
    {
        return $this->adminTraiteur;
    }

    public function setAdminTraiteur(?Administrateur $adminTraiteur): self
    {
        $this->adminTraiteur = $adminTraiteur;
        return $this;
    }

    public function setCommande(?Commande $commande): self
    {
        $this->commande = $commande;
        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): self
    {
        $this->motif = $motif;
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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function setResolution(?string $resolution): self
    {
        $this->resolution = $resolution;
        return $this;
    }

    public function traiter(?Administrateur $admin = null): void
    {
        if ($this->statut !== StatutLitige::OUVERT->value) {
            throw new \RuntimeException('Seul un litige ouvert peut être traité.');
        }
        if ($admin !== null) {
            $this->adminTraiteur = $admin;
        }
        $this->statut = StatutLitige::EN_TRAITEMENT->value;
    }

    public function resoudre(?string $resolution = null): void
    {
        if ($this->statut !== StatutLitige::EN_TRAITEMENT->value) {
            throw new \RuntimeException('Le litige doit être en traitement pour être résolu.');
        }
        $this->statut = StatutLitige::RESOLU->value;
        $this->resolution = $resolution;
    }
}
