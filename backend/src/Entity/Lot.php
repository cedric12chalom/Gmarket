<?php

namespace App\Entity;

use App\Enum\IndiceFraicheur;
use App\Enum\StatutLot;
use App\Repository\LotRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LotRepository::class)]
#[ORM\Table(uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_lot_qr_code', columns: ['qr_code'])])]
class Lot
{
    public ?float $distanceKm = null;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $quantiteDisponible = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $quantiteReservee = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prixProducteur = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateRecolte = null;

    #[ORM\Column]
    private ?int $dureeConservation = null;

    #[ORM\ManyToOne(targetEntity: Variete::class)]
    #[ORM\JoinColumn(name: 'variete_id', nullable: true)]
    private ?Variete $variete = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieuProduction = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $qrCode = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\ManyToOne(targetEntity: Producteur::class, inversedBy: 'lots')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Producteur $producteur = null;

    #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'lots')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Produit $produit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $longitude = null;

    public function __construct()
    {
        $this->statut = StatutLot::DISPONIBLE->value;
        $this->quantiteReservee = '0.00';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantiteDisponible(): ?string
    {
        return $this->quantiteDisponible;
    }

    public function setQuantiteDisponible(?string $quantiteDisponible): self
    {
        $this->quantiteDisponible = $quantiteDisponible;
        return $this;
    }

    public function getQuantiteReservee(): ?string
    {
        return $this->quantiteReservee;
    }

    public function setQuantiteReservee(?string $quantiteReservee): self
    {
        $this->quantiteReservee = $quantiteReservee;
        return $this;
    }

    public function getPrixProducteur(): ?string
    {
        return $this->prixProducteur;
    }

    public function setPrixProducteur(?string $prixProducteur): self
    {
        $this->prixProducteur = $prixProducteur;
        return $this;
    }

    public function getDateRecolte(): ?\DateTimeInterface
    {
        return $this->dateRecolte;
    }

    public function setDateRecolte(\DateTimeInterface $dateRecolte): self
    {
        $this->dateRecolte = $dateRecolte;
        return $this;
    }

    public function getDureeConservation(): ?int
    {
        return $this->dureeConservation;
    }

    public function setDureeConservation(int $dureeConservation): self
    {
        $this->dureeConservation = $dureeConservation;
        return $this;
    }

    public function getVariete(): ?Variete
    {
        return $this->variete;
    }

    public function setVariete(?Variete $variete): static
    {
        $this->variete = $variete;
        return $this;
    }

    public function getLieuProduction(): ?string
    {
        return $this->lieuProduction;
    }

    public function setLieuProduction(?string $lieuProduction): self
    {
        $this->lieuProduction = $lieuProduction;
        return $this;
    }

    public function getQrCode(): ?string
    {
        return $this->qrCode;
    }

    public function setQrCode(?string $qrCode): self
    {
        $this->qrCode = $qrCode;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getProducteur(): ?Producteur
    {
        return $this->producteur;
    }

    public function setProducteur(?Producteur $producteur): self
    {
        $this->producteur = $producteur;
        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): self
    {
        $this->produit = $produit;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getDateExpiration(): ?\DateTimeInterface
    {
        if (!$this->dateRecolte || !$this->dureeConservation) {
            return null;
        }
        return (clone $this->dateRecolte)->modify('+' . $this->dureeConservation . ' days');
    }

    public function getJoursAvantRetrait(): ?int
    {
        $expiration = $this->getDateExpiration();
        if (!$expiration) {
            return null;
        }
        $today = new \DateTime('today');
        $retrait = (clone $expiration)->modify('+1 day');
        return (int) $retrait->diff($today)->format('%r%a');
    }

    public function getQuantiteRestante(): float
    {
        return (float) $this->quantiteDisponible - (float) $this->quantiteReservee;
    }

    public function calculerIndiceFraicheur(): string
    {
        if (!$this->dateRecolte) {
            return IndiceFraicheur::EXPIRE->value;
        }
        $now = new \DateTime();
        $interval = $now->diff($this->dateRecolte);
        $jours = (int) $interval->format('%a');

        if ($jours > 7 || $jours > (int) $this->dureeConservation) {
            return IndiceFraicheur::EXPIRE->value;
        }
        if ($jours <= 2) {
            return IndiceFraicheur::TRES_FRAIS->value;
        }
        if ($jours <= 5) {
            return IndiceFraicheur::FRAIS->value;
        }
        return IndiceFraicheur::A_CONSOMMER->value;
    }

    public function calculerPrixReduit(): ?float
    {
        if (!$this->dateRecolte || !$this->dureeConservation) {
            return null;
        }

        $expiration = (clone $this->dateRecolte)->modify('+' . $this->dureeConservation . ' days');
        $maintenant = new \DateTime();
        $diffJours = (int) $expiration->diff($maintenant)->format('%r%a');
        if ($diffJours >= 0 && $diffJours <= 1) {
            return round((float) $this->prixProducteur * 0.7, 2);
        }

        return null;
    }

    public function genererQRCode(): string
    {
        $data = [
            'id' => $this->id,
            'producteur' => [
                'id' => $this->producteur?->getId(),
                'nom' => $this->producteur?->getNom(),
                'prenom' => $this->producteur?->getPrenom(),
                'localisation' => $this->producteur?->getLocalisation(),
            ],
            'date_recolte' => $this->dateRecolte?->format('Y-m-d'),
            'lieu_production' => $this->lieuProduction,
            'variete' => $this->variete?->getNom(),
            'quantite' => $this->quantiteDisponible,
        ];
        return 'TL-' . ($this->id ?? 0) . '-' . str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($data))) . '-' . bin2hex(random_bytes(4));
        return $code;
    }

    public function reserverQuantite(float $qte): void
    {
        $disponible = (float) $this->quantiteDisponible;
        $reservee = (float) $this->quantiteReservee;
        if ($disponible - $reservee < $qte) {
            throw new \RuntimeException('Stock insuffisant pour réserver la quantité demandée.');
        }
        $this->quantiteDisponible = bcsub($disponible, $qte, 2);
        $this->quantiteReservee = bcadd($reservee, $qte, 2);
    }

    public function libererQuantite(float $qte): void
    {
        $reservee = (float) $this->quantiteReservee;
        if ($reservee < $qte) {
            throw new \RuntimeException('Impossible de libérer une quantité supérieure à la quantité réservée.');
        }
        $this->quantiteDisponible = bcadd((float) $this->quantiteDisponible, $qte, 2);
        $this->quantiteReservee = bcsub($reservee, $qte, 2);
    }

    public function retirer(): void
    {
        $this->statut = StatutLot::RETIRE->value;
    }
}
