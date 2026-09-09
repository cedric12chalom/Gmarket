<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'produits', targetEntity: Boutique::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Boutique $boutique = null;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'produits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Categorie $categorie = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Photos obligatoires : au moins une URL avant publication.
     *
     * @var array<int, string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $images = [];

    #[ORM\Column]
    private float $prix = 0.0;

    #[ORM\Column(options: ['default' => true])]
    private bool $actif = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Variante>
     */
    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: Variante::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $variantes;

    #[ORM\OneToOne(mappedBy: 'produit', targetEntity: Offre::class, cascade: ['persist', 'remove'])]
    private ?Offre $offre = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->variantes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * @param array<int, string> $images
     */
    public function setImages(array $images): static
    {
        $this->images = array_values($images);
        return $this;
    }

    public function addImage(string $image): static
    {
        $this->images[] = $image;
        return $this;
    }

    public function hasImages(): bool
    {
        return count($this->images) > 0;
    }

    public function getImagePrincipale(): ?string
    {
        return $this->images[0] ?? null;
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

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, Variante>
     */
    public function getVariantes(): Collection
    {
        return $this->variantes;
    }

    public function addVariante(Variante $variante): static
    {
        if (!$this->variantes->contains($variante)) {
            $this->variantes->add($variante);
            $variante->setProduit($this);
        }
        return $this;
    }

    public function removeVariante(Variante $variante): static
    {
        $this->variantes->removeElement($variante);
        return $this;
    }

    public function getOffre(): ?Offre
    {
        return $this->offre;
    }

    public function setOffre(?Offre $offre): static
    {
        $this->offre = $offre;
        return $this;
    }

    /**
     * Stock total disponible (somme des variantes).
     */
    public function getStockTotal(): int
    {
        $total = 0;
        foreach ($this->variantes as $variante) {
            $total += $variante->getStock();
        }
        return $total;
    }

    /**
     * Total vendu (somme des quantités vendues par variante).
     */
    public function getTotalVendu(): int
    {
        $total = 0;
        foreach ($this->variantes as $variante) {
            $total += $variante->getStockVendu();
        }
        return $total;
    }

    /**
     * Prix effectif au moment où l'on consulte : prix promo si une offre
     * est en cours, sinon prix de base.
     */
    public function getPrixActuel(): float
    {
        $offer = $this->offre;
        if ($offer && $offer->estEnCours()) {
            return $offer->getPrixPromo();
        }
        return $this->prix;
    }

    /**
     * Couleurs distinctes proposées par les variantes.
     *
     * @return array<int, string>
     */
    public function getCouleurs(): array
    {
        $couleurs = [];
        foreach ($this->variantes as $variante) {
            if (!in_array($variante->getCouleur(), $couleurs, true)) {
                $couleurs[] = $variante->getCouleur();
            }
        }
        return $couleurs;
    }

    /**
     * Tailles distinctes proposées par les variantes.
     *
     * @return array<int, string>
     */
    public function getTailles(): array
    {
        $tailles = [];
        foreach ($this->variantes as $variante) {
            if (!in_array($variante->getTaille(), $tailles, true)) {
                $tailles[] = $variante->getTaille();
            }
        }
        return $tailles;
    }
}