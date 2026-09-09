<?php

namespace App\Entity;

use App\Repository\AbonnementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AbonnementRepository::class)]
class Abonnement
{
    public const ESSAI_JOURS = 30; // Période d'essai gratuite (constante métier).

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120, unique: true)]
    #[Assert\NotBlank]
    private ?string $nom = null;

    #[ORM\Column(length: 60, unique: true)]
    #[Assert\NotBlank]
    private ?string $code = null;

    #[ORM\Column]
    #[Assert\GreaterThanOrEqual(0)]
    private float $prix = 0.0;

    #[ORM\Column(name: 'duree_jours')]
    #[Assert\GreaterThan(0)]
    private int $dureeJours = 30;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $avantages = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $actif = true;

    /**
     * @var Collection<int, AbonnementSouscription>
     */
    #[ORM\OneToMany(mappedBy: 'abonnement', targetEntity: AbonnementSouscription::class)]
    private Collection $souscriptions;

    public function __construct()
    {
        $this->souscriptions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getPrix(): float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getDureeJours(): int
    {
        return $this->dureeJours;
    }

    public function setDureeJours(int $dureeJours): static
    {
        $this->dureeJours = $dureeJours;
        return $this;
    }

    public function getAvantages(): ?string
    {
        return $this->avantages;
    }

    public function setAvantages(?string $avantages): static
    {
        $this->avantages = $avantages;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    /**
     * @return Collection<int, AbonnementSouscription>
     */
    public function getSouscriptions(): Collection
    {
        return $this->souscriptions;
    }
}