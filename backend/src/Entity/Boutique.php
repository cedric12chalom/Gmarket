<?php

namespace App\Entity;

use App\Enum\BoutiqueTheme;
use App\Repository\BoutiqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BoutiqueRepository::class)]
class Boutique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'boutiques', targetEntity: Vendeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vendeur $vendeur = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    private ?string $nom = null;

    #[ORM\Column(type: Types::STRING, length: 120, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-z0-9-]+$/')]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $banniere = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(length: 30)]
    private string $theme = BoutiqueTheme::CLASSIQUE->value;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $tiktokPseudo = null;

    // Livraison V1 simplifiée : simple déclaration, pas de logistique.
    #[ORM\Column(name: 'a_livreur', options: ['default' => false])]
    private bool $aLivreur = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $livreurDetail = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** Palette personnalisée : {accent, primary, secondary, background}. */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $customColors = null;

    /**
     * @var Collection<int, Produit>
     */
    #[ORM\OneToMany(mappedBy: 'boutique', targetEntity: Produit::class, cascade: ['persist', 'remove'])]
    private Collection $produits;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->produits = new ArrayCollection();
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

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getBanniere(): ?string
    {
        return $this->banniere;
    }

    public function setBanniere(?string $banniere): static
    {
        $this->banniere = $banniere;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function getTheme(): BoutiqueTheme
    {
        return BoutiqueTheme::tryFrom($this->theme) ?? BoutiqueTheme::CLASSIQUE;
    }

    public function setTheme(string|BoutiqueTheme $theme): static
    {
        $this->theme = $theme instanceof BoutiqueTheme ? $theme->value : $theme;
        return $this;
    }

    public function getTiktokPseudo(): ?string
    {
        return $this->tiktokPseudo;
    }

    public function setTiktokPseudo(?string $tiktokPseudo): static
    {
        $this->tiktokPseudo = $tiktokPseudo;
        return $this;
    }

    public function getALivreur(): bool
    {
        return $this->aLivreur;
    }

    public function setALivreur(bool $aLivreur): static
    {
        $this->aLivreur = $aLivreur;
        return $this;
    }

    public function getLivreurDetail(): ?string
    {
        return $this->livreurDetail;
    }

    public function setLivreurDetail(?string $livreurDetail): static
    {
        $this->livreurDetail = $livreurDetail;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Lien unique partageable (ex : plateforme.com/boutique/nom-vendeur).
     */
    public function getLienPartageable(): string
    {
        return '/boutique/' . $this->slug;
    }

    public function getCustomColors(): ?array
    {
        return $this->customColors;
    }

    public function setCustomColors(?array $customColors): static
    {
        $this->customColors = $customColors;
        return $this;
    }

    /** Retourne la couleur accent effective : customColors['accent'] ou le thème par défaut. */
    public function getAccentColor(): string
    {
        if ($this->customColors && isset($this->customColors['accent'])) {
            return $this->customColors['accent'];
        }
        return $this->getTheme()->accent();
    }

    /** Retourne la palette complète (custom ou thème). */
    public function getPalette(): array
    {
        $themeAccent = $this->getTheme()->accent();
        return [
            'accent' => $this->customColors['accent'] ?? $themeAccent,
            'primary' => $this->customColors['primary'] ?? '#1e293b',
            'secondary' => $this->customColors['secondary'] ?? '#64748b',
            'background' => $this->customColors['background'] ?? '#ffffff',
        ];
    }

    /**
     * @return Collection<int, Produit>
     */
    public function getProduits(): Collection
    {
        return $this->produits;
    }

    public function addProduit(Produit $produit): static
    {
        if (!$this->produits->contains($produit)) {
            $this->produits->add($produit);
            $produit->setBoutique($this);
        }
        return $this;
    }

    public function removeProduit(Produit $produit): static
    {
        $this->produits->removeElement($produit);
        return $this;
    }
}