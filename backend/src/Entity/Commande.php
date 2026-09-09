<?php

namespace App\Entity;

use App\Enum\StatutCommande;
use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Acheteur::class, inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Acheteur $acheteur = null;

    #[ORM\ManyToOne(targetEntity: Boutique::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Boutique $boutique = null;

    #[ORM\Column(length: 30)]
    private string $statut = StatutCommande::EN_ATTENTE->value;

    #[ORM\Column(name: 'montant_total')]
    #[Assert\GreaterThanOrEqual(0)]
    private float $montantTotal = 0.0;

    #[ORM\Column(name: 'montant_produits')]
    #[Assert\GreaterThanOrEqual(0)]
    private float $montantProduits = 0.0;

    #[ORM\Column]
    #[Assert\GreaterThanOrEqual(0)]
    private float $commission = 0.0;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, LigneCommande>
     */
    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: LigneCommande::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignes;

    #[ORM\OneToOne(mappedBy: 'commande', targetEntity: Paiement::class, cascade: ['persist', 'remove'])]
    private ?Paiement $paiement = null;

    #[ORM\OneToOne(mappedBy: 'commande', targetEntity: Avis::class, cascade: ['persist', 'remove'])]
    private ?Avis $avis = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->lignes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAcheteur(): ?Acheteur
    {
        return $this->acheteur;
    }

    public function setAcheteur(?Acheteur $acheteur): static
    {
        $this->acheteur = $acheteur;
        return $this;
    }

    public function getBoutique(): ?Boutique
    {
        return $this->boutique;
    }

    public function setBoutique(?Boutique $boutique): static
    {
        $this->boutique = $boutique;
        return $this;
    }

    public function getStatut(): StatutCommande
    {
        return StatutCommande::tryFrom($this->statut) ?? StatutCommande::EN_ATTENTE;
    }

    public function setStatut(string|StatutCommande $statut): static
    {
        $this->statut = $statut instanceof StatutCommande ? $statut->value : $statut;
        return $this;
    }

    public function getMontantTotal(): float
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(float $montantTotal): static
    {
        $this->montantTotal = round($montantTotal, 2);
        return $this;
    }

    public function getMontantProduits(): float
    {
        return $this->montantProduits;
    }

    public function setMontantProduits(float $montantProduits): static
    {
        $this->montantProduits = round($montantProduits, 2);
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, LigneCommande>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(LigneCommande $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setCommande($this);
        }
        return $this;
    }

    public function removeLigne(LigneCommande $ligne): static
    {
        $this->lignes->removeElement($ligne);
        return $this;
    }

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(?Paiement $paiement): static
    {
        $this->paiement = $paiement;
        return $this;
    }

    public function getAvis(): ?Avis
    {
        return $this->avis;
    }

    public function setAvis(?Avis $avis): static
    {
        $this->avis = $avis;
        return $this;
    }
}