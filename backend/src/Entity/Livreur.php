<?php

namespace App\Entity;

use App\Repository\LivreurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LivreurRepository::class)]
class Livreur extends User
{
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $disponible = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $chatActif = true;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephoneService = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $cniNumero = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cniPhoto = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoProfil = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $moyenDeplacement = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $vehiculePlaque = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $vehiculeMarqueModele = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mobileMoneyNumero = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactUrgenceNom = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $contactUrgenceTelephone = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $conditionsLivraisonAccepteLe = null;

    #[ORM\Column(length: 30, options: ['default' => 'en_attente'])]
    private string $statutVerification = 'en_attente';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motifRefus = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $verifieLe = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $valideLe = null;

    #[ORM\OneToMany(targetEntity: Livraison::class, mappedBy: 'livreur')]
    private Collection $livraisons;

    public function __construct()
    {
        parent::__construct();
        $this->livraisons = new ArrayCollection();
    }

    public function isDisponible(): bool
    {
        return $this->disponible;
    }

    public function setDisponible(bool $disponible): self
    {
        $this->disponible = $disponible;
        return $this;
    }

    public function isChatActif(): bool
    {
        return $this->chatActif;
    }

    public function setChatActif(bool $chatActif): self
    {
        $this->chatActif = $chatActif;
        return $this;
    }

    public function getTelephoneService(): ?string
    {
        return $this->telephoneService;
    }

    public function setTelephoneService(?string $telephoneService): self
    {
        $this->telephoneService = $telephoneService;
        return $this;
    }

    public function getCniNumero(): ?string
    {
        return $this->cniNumero;
    }

    public function setCniNumero(?string $cniNumero): self
    {
        $this->cniNumero = $cniNumero;
        return $this;
    }

    public function getCniPhoto(): ?string
    {
        return $this->cniPhoto;
    }

    public function setCniPhoto(?string $cniPhoto): self
    {
        $this->cniPhoto = $cniPhoto;
        return $this;
    }

    public function getPhotoProfil(): ?string
    {
        return $this->photoProfil;
    }

    public function setPhotoProfil(?string $photoProfil): self
    {
        $this->photoProfil = $photoProfil;
        return $this;
    }

    public function getMoyenDeplacement(): ?string
    {
        return $this->moyenDeplacement;
    }

    public function setMoyenDeplacement(?string $moyenDeplacement): self
    {
        $this->moyenDeplacement = $moyenDeplacement;
        return $this;
    }

    public function getVehiculePlaque(): ?string
    {
        return $this->vehiculePlaque;
    }

    public function setVehiculePlaque(?string $vehiculePlaque): self
    {
        $this->vehiculePlaque = $vehiculePlaque;
        return $this;
    }

    public function getVehiculeMarqueModele(): ?string
    {
        return $this->vehiculeMarqueModele;
    }

    public function setVehiculeMarqueModele(?string $vehiculeMarqueModele): self
    {
        $this->vehiculeMarqueModele = $vehiculeMarqueModele;
        return $this;
    }

    public function getMobileMoneyNumero(): ?string
    {
        return $this->mobileMoneyNumero;
    }

    public function setMobileMoneyNumero(?string $mobileMoneyNumero): self
    {
        $this->mobileMoneyNumero = $mobileMoneyNumero;
        return $this;
    }

    public function getContactUrgenceNom(): ?string
    {
        return $this->contactUrgenceNom;
    }

    public function setContactUrgenceNom(?string $contactUrgenceNom): self
    {
        $this->contactUrgenceNom = $contactUrgenceNom;
        return $this;
    }

    public function getContactUrgenceTelephone(): ?string
    {
        return $this->contactUrgenceTelephone;
    }

    public function setContactUrgenceTelephone(?string $contactUrgenceTelephone): self
    {
        $this->contactUrgenceTelephone = $contactUrgenceTelephone;
        return $this;
    }

    public function getConditionsLivraisonAccepteLe(): ?\DateTimeInterface
    {
        return $this->conditionsLivraisonAccepteLe;
    }

    public function setConditionsLivraisonAccepteLe(?\DateTimeInterface $conditionsLivraisonAccepteLe): self
    {
        $this->conditionsLivraisonAccepteLe = $conditionsLivraisonAccepteLe;
        return $this;
    }

    public function getStatutVerification(): string
    {
        return $this->statutVerification;
    }

    public function setStatutVerification(string $statutVerification): self
    {
        $this->statutVerification = $statutVerification;
        return $this;
    }

    public function getMotifRefus(): ?string
    {
        return $this->motifRefus;
    }

    public function setMotifRefus(?string $motifRefus): self
    {
        $this->motifRefus = $motifRefus;
        return $this;
    }

    public function getVerifieLe(): ?\DateTimeInterface
    {
        return $this->verifieLe;
    }

    public function setVerifieLe(?\DateTimeInterface $verifieLe): self
    {
        $this->verifieLe = $verifieLe;
        return $this;
    }

    public function getValideLe(): ?\DateTimeInterface
    {
        return $this->valideLe;
    }

    public function setValideLe(?\DateTimeInterface $valideLe): self
    {
        $this->valideLe = $valideLe;
        return $this;
    }

    public function estEligibleAuxMissions(): bool
    {
        return $this->statutVerification === \App\Enum\StatutVerificationLivreur::VALIDE->value;
    }

    public function getLivraisons(): Collection
    {
        return $this->livraisons;
    }

    public function addLivraison(Livraison $livraison): self
    {
        if (!$this->livraisons->contains($livraison)) {
            $this->livraisons->add($livraison);
            $livraison->setLivreur($this);
        }
        return $this;
    }

    public function removeLivraison(Livraison $livraison): self
    {
        if ($this->livraisons->removeElement($livraison)) {
            if ($livraison->getLivreur() === $this) {
                $livraison->setLivreur(null);
            }
        }
        return $this;
    }

    // Use-case: accepterMission() — accepts a livraison (delegates to Livraison::accepter with RG15 check).
    public function accepterMission(Livraison $livraison): bool
    {
        $livraison->accepter($this);
        return true;
    }

    // Use-case: confirmerLivraison() — confirms delivery with a photo (delegates to Livraison::confirmerLivraison).
    public function confirmerLivraison(Livraison $livraison, string $photoLivraison): bool
    {
        $livraison->confirmerLivraison($photoLivraison);
        return true;
    }
}
