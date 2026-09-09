<?php

namespace App\Entity;

use App\Enum\StatutUser;
use App\Enum\TypeTransport;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'role', type: 'string', length: 20)]
#[ORM\DiscriminatorMap([
    'user' => User::class,
    'producteur' => Producteur::class,
    'acheteur' => Acheteur::class,
    'livreur' => Livreur::class,
    'admin' => Administrateur::class,
])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $googleId = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $localisation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $quartier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresseLivraison = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $typeTransport = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $solde = '0.00';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cguAccepteLe = null;

    #[ORM\Column]
    private bool $emailVerifie = false;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $codeVerification = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $codeVerificationExpireLe = null;

    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'acheteur')]
    private \Doctrine\Common\Collections\Collection $commandes;

    public function __construct()
    {
        $this->statut = StatutUser::ACTIF->value;
        $this->roles = [];
        $this->dateInscription = new \DateTime();
        $this->commandes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getCommandes(): \Doctrine\Common\Collections\Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): self
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setAcheteur($this);
        }
        return $this;
    }

    public function removeCommande(Commande $commande): self
    {
        if ($this->commandes->removeElement($commande)) {
            if ($commande->getAcheteur() === $this) {
                $commande->setAcheteur(null);
            }
        }
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRole(): string
    {
        return match (true) {
            $this instanceof Producteur => 'producteur',
            $this instanceof Acheteur => 'acheteur',
            $this instanceof Livreur => 'livreur',
            $this instanceof Administrateur => 'admin',
            default => 'user',
        };
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        $roles[] = 'ROLE_' . strtoupper($this->getRole());
        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
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

    public function isActif(): bool
    {
        return $this->statut === StatutUser::ACTIF->value;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): self
    {
        $this->localisation = $localisation;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville;
        return $this;
    }

    public function getQuartier(): ?string
    {
        return $this->quartier;
    }

    public function setQuartier(?string $quartier): self
    {
        $this->quartier = $quartier;
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

    public function getTypeTransport(): ?string
    {
        return $this->typeTransport;
    }

    public function setTypeTransport(?string $typeTransport): self
    {
        $this->typeTransport = $typeTransport;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getSolde(): ?string
    {
        return $this->solde;
    }

    public function setSolde(string $solde): self
    {
        $this->solde = $solde;
        return $this;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(?\DateTimeInterface $dateInscription): self
    {
        $this->dateInscription = $dateInscription;
        return $this;
    }

    public function getCguAccepteLe(): ?\DateTimeInterface
    {
        return $this->cguAccepteLe;
    }

    public function setCguAccepteLe(?\DateTimeInterface $cguAccepteLe): self
    {
        $this->cguAccepteLe = $cguAccepteLe;
        return $this;
    }

    public function isEmailVerifie(): bool
    {
        return $this->emailVerifie;
    }

    public function setEmailVerifie(bool $emailVerifie): self
    {
        $this->emailVerifie = $emailVerifie;
        return $this;
    }

    public function getCodeVerification(): ?string
    {
        return $this->codeVerification;
    }

    public function setCodeVerification(?string $codeVerification): self
    {
        $this->codeVerification = $codeVerification;
        return $this;
    }

    public function getCodeVerificationExpireLe(): ?\DateTimeInterface
    {
        return $this->codeVerificationExpireLe;
    }

    public function setCodeVerificationExpireLe(?\DateTimeInterface $codeVerificationExpireLe): self
    {
        $this->codeVerificationExpireLe = $codeVerificationExpireLe;
        return $this;
    }

    // Diagram use-case: seConnecter() — authentication is a controller/TokenService concern; returns the user identifier used at login.
    public function seConnecter(?string $plainPassword = null): bool
    {
        return $this->isActif() && $this->email !== null;
    }

    // Diagram use-case: gererProfil() — returns the profile data reachable for this user (mirrors EntitySerializer::user()).
    public function gererProfil(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'role' => $this->getRole(),
            'statut' => $this->statut,
            'adresse_livraison' => $this->adresseLivraison,
            'localisation' => $this->localisation,
            'ville' => $this->ville,
            'quartier' => $this->quartier,
        ];
    }
}
