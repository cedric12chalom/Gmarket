<?php

namespace App\Entity;

use App\Enum\StatutLivraison;
use App\Enum\TypeTransport;
use App\Repository\LivraisonRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LivraisonRepository::class)]
class Livraison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Commande::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    #[ORM\ManyToOne(targetEntity: Livreur::class, inversedBy: 'livraisons')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Livreur $livreur = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresseLivraison = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateRetrait = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateLivraison = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateReceptionConfirmee = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoRetrait = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoLivraison = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $frais = '0.00';

    public function __construct()
    {
        $this->statut = StatutLivraison::ASSIGNEE->value;
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

    public function getLivreur(): ?Livreur
    {
        return $this->livreur;
    }

    public function setLivreur(?Livreur $livreur): self
    {
        $this->livreur = $livreur;
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

    public function getAdresseLivraison(): ?string
    {
        return $this->adresseLivraison;
    }

    public function setAdresseLivraison(?string $adresseLivraison): self
    {
        $this->adresseLivraison = $adresseLivraison;
        return $this;
    }

    public function getDateRetrait(): ?\DateTimeInterface
    {
        return $this->dateRetrait;
    }

    public function setDateRetrait(?\DateTimeInterface $dateRetrait): self
    {
        $this->dateRetrait = $dateRetrait;
        return $this;
    }

    public function getDateLivraison(): ?\DateTimeInterface
    {
        return $this->dateLivraison;
    }

    public function setDateLivraison(?\DateTimeInterface $dateLivraison): self
    {
        $this->dateLivraison = $dateLivraison;
        return $this;
    }

    public function getDateReceptionConfirmee(): ?\DateTimeInterface
    {
        return $this->dateReceptionConfirmee;
    }

    public function setDateReceptionConfirmee(?\DateTimeInterface $dateReceptionConfirmee): self
    {
        $this->dateReceptionConfirmee = $dateReceptionConfirmee;
        return $this;
    }

    public function isReceptionConfirmee(): bool
    {
        return $this->dateReceptionConfirmee !== null;
    }

    public function getPhotoRetrait(): ?string
    {
        return $this->photoRetrait;
    }

    public function setPhotoRetrait(?string $photoRetrait): self
    {
        $this->photoRetrait = $photoRetrait;
        return $this;
    }

    public function getPhotoLivraison(): ?string
    {
        return $this->photoLivraison;
    }

    public function setPhotoLivraison(?string $photoLivraison): self
    {
        $this->photoLivraison = $photoLivraison;
        return $this;
    }

    public function getFrais(): ?string
    {
        return $this->frais;
    }

    public function setFrais(float|string $frais): self
    {
        $this->frais = (string) $frais;
        return $this;
    }

    public function accepter(Livreur $livreur): void
    {
        if ($this->statut !== StatutLivraison::ASSIGNEE->value) {
            throw new \RuntimeException('La livraison doit être assignée pour être acceptée.');
        }
        if ($livreur->getTypeTransport() === null || $livreur->getTypeTransport() !== $this->commande?->getLignes()?->first()?->getLot()?->getProduit()?->getCategorie()?->getTypeTransport()) {
            // RG15: le type de transport du livreur doit correspondre au type requis par la catégorie.
            throw new \RuntimeException('Le type de transport du livreur ne correspond pas à celui requis pour cette livraison.');
        }
        $this->livreur = $livreur;
        $this->statut = StatutLivraison::EN_COURS->value;
    }

    /**
     * Assigne directement un livreur choisi par l'acheteur (option (a) : auto-confirmation).
     * RG15 est appliqué via le type de transport requis.
     */
    public function assignerParAcheteur(Livreur $livreur): void
    {
        if ($this->statut !== StatutLivraison::ASSIGNEE->value || $this->livreur !== null) {
            throw new \RuntimeException('La livraison doit être en attente et non assignée.');
        }
        $typeTransportRequis = $this->commande?->getLignes()?->first()?->getLot()?->getProduit()?->getCategorie()?->getTypeTransport();
        if ($typeTransportRequis !== null && $livreur->getTypeTransport() !== $typeTransportRequis) {
            throw new \RuntimeException('Le type de transport du livreur ne correspond pas à celui requis pour cette livraison.');
        }
        $this->livreur = $livreur;
        $this->statut = StatutLivraison::EN_COURS->value;
    }

    public function confirmerLivraison(string $photoLivraison): void
    {
        if ($this->statut !== StatutLivraison::EN_COURS->value) {
            throw new \RuntimeException('La livraison doit être en cours pour confirmer la livraison.');
        }
        $this->photoLivraison = $photoLivraison;
        $this->dateLivraison = new \DateTime();
        $this->statut = StatutLivraison::LIVREE->value;
    }

    /**
     * Confirme la réception par l'acheteur (trust/audit signal).
     * Peut être appelée une seule fois, après la livraison.
     */
    public function confirmerReception(): void
    {
        if ($this->statut !== StatutLivraison::LIVREE->value) {
            throw new \RuntimeException('La livraison doit être livrée pour confirmer la réception.');
        }
        if ($this->dateReceptionConfirmee !== null) {
            throw new \RuntimeException('La réception a déjà été confirmée.');
        }
        $this->dateReceptionConfirmee = new \DateTime();
    }
}
