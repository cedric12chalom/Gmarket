<?php

namespace App\Entity;

use App\Repository\OffreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OffreRepository::class)]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'offre', targetEntity: Produit::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Produit $produit = null;

    #[ORM\Column]
    #[Assert\GreaterThan(0)]
    private float $prixPromo = 0.0;

    #[ORM\Column(name: 'date_debut', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(name: 'date_fin', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateFin = null;

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

    public function getPrixPromo(): float
    {
        return $this->prixPromo;
    }

    public function setPrixPromo(float $prixPromo): static
    {
        $this->prixPromo = $prixPromo;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function estEnCours(?\DateTimeImmutable $now = null): bool
    {
        $now ??= new \DateTimeImmutable();
        $debutOk = $this->dateDebut === null || $this->dateDebut <= $now;
        $finOk = $this->dateFin === null || $this->dateFin >= $now;
        return $debutOk && $finOk;
    }

    /**
     * Secondes restantes avant la fin de l'offre (pour le compte à rebours).
     */
    public function secondesRestantes(?\DateTimeImmutable $now = null): int
    {
        if ($this->dateFin === null) {
            return 0;
        }
        $now ??= new \DateTimeImmutable();
        return max(0, $this->dateFin->getTimestamp() - $now->getTimestamp());
    }

    public function pourcentageReduction(): int
    {
        if ($this->produit === null || $this->produit->getPrix() <= 0) {
            return 0;
        }
        return (int) round(((1 - $this->prixPromo / $this->produit->getPrix()) * 100));
    }
}