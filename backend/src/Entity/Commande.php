<?php

namespace App\Entity;

use App\Enum\ModeRecuperation;
use App\Enum\StatutCommande;
use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $acheteur = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column(length: 20)]
    private ?string $modeRecuperation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCommande = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantProduits = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $commission = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $fraisLivraison = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantTotal = '0.00';

    #[ORM\OneToMany(targetEntity: LigneCommande::class, mappedBy: 'commande', cascade: ['persist'])]
    private Collection $lignes;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
        $this->statut = StatutCommande::EN_ATTENTE->value;
        $this->dateCommande = new \DateTime();
        $this->modeRecuperation = ModeRecuperation::LIVRAISON->value;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAcheteur(): ?User
    {
        return $this->acheteur;
    }

    public function setAcheteur(?User $acheteur): self
    {
        $this->acheteur = $acheteur;
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

    public function getModeRecuperation(): ?string
    {
        return $this->modeRecuperation;
    }

    public function setModeRecuperation(string $modeRecuperation): self
    {
        $this->modeRecuperation = $modeRecuperation;
        return $this;
    }

    public function getDateCommande(): ?\DateTimeInterface
    {
        return $this->dateCommande;
    }

    public function setDateCommande(?\DateTimeInterface $dateCommande): self
    {
        $this->dateCommande = $dateCommande;
        return $this;
    }

    public function getMontantProduits(): ?string
    {
        return $this->montantProduits;
    }

    public function setMontantProduits(?string $montantProduits): self
    {
        $this->montantProduits = $montantProduits;
        return $this;
    }

    public function getCommission(): ?string
    {
        return $this->commission;
    }

    public function setCommission(?string $commission): self
    {
        $this->commission = $commission;
        return $this;
    }

    public function getFraisLivraison(): ?string
    {
        return $this->fraisLivraison;
    }

    public function setFraisLivraison(?string $fraisLivraison): self
    {
        $this->fraisLivraison = $fraisLivraison;
        return $this;
    }

    public function getMontantTotal(): ?string
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(?string $montantTotal): self
    {
        $this->montantTotal = $montantTotal;
        return $this;
    }

    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(LigneCommande $ligne): self
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setCommande($this);
        }
        return $this;
    }

    public function calculerSousTotal(): float
    {
        $total = 0.0;
        foreach ($this->lignes as $ligne) {
            $total += $ligne->getSousTotal();
        }
        return $total;
    }

    public function calculerTotal(): float
    {
        return (float) $this->calculerSousTotal() + (float) $this->commission + (float) $this->fraisLivraison;
    }

    public function confirmer(): void
    {
        if ($this->statut !== StatutCommande::EN_ATTENTE->value) {
            throw new \RuntimeException('La commande ne peut être confirmée que si elle est en attente.');
        }
        foreach ($this->lignes as $ligne) {
            $lot = $ligne->getLot();
            if ($lot === null || $lot->getQuantiteRestante() < $ligne->getQuantite()) {
                throw new \RuntimeException('Stock insuffisant pour confirmer la commande.');
            }
        }
        foreach ($this->lignes as $ligne) {
            $ligne->getLot()?->reserverQuantite($ligne->getQuantite());
        }
        $this->statut = StatutCommande::CONFIRMEE->value;
    }

    public function annuler(): void
    {
        if ($this->statut === StatutCommande::ANNULEE->value) {
            throw new \RuntimeException('La commande est déjà annulée.');
        }
        if (
            $this->statut === StatutCommande::CONFIRMEE->value
            || $this->statut === StatutCommande::PAYEE->value
            || $this->statut === StatutCommande::EN_COURS->value
        ) {
            foreach ($this->lignes as $ligne) {
                $ligne->getLot()?->libererQuantite($ligne->getQuantite());
            }
        }
        $this->statut = StatutCommande::ANNULEE->value;
    }

    public function estEnRetardDeConfirmation(int $delaiHeures): bool
    {
        $limite = (clone $this->dateCommande)->modify("+{$delaiHeures} hours");
        return new \DateTime() > $limite;
    }
}
