<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Vendeur extends User
{
    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $prenom = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $nom = null;

    /**
     * @var Collection<int, Boutique>
     */
    #[ORM\OneToMany(mappedBy: 'vendeur', targetEntity: Boutique::class, cascade: ['persist', 'remove'])]
    protected Collection $boutiques;

    /**
     * @var Collection<int, AbonnementSouscription>
     */
    #[ORM\OneToMany(mappedBy: 'vendeur', targetEntity: AbonnementSouscription::class, cascade: ['persist', 'remove'])]
    protected Collection $abonnements;

    public function __construct()
    {
        parent::__construct();
        $this->boutiques = new ArrayCollection();
        $this->abonnements = new ArrayCollection();
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenomNom(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? ''));
    }

    /**
     * @return Collection<int, Boutique>
     */
    public function getBoutiques(): Collection
    {
        return $this->boutiques;
    }

    public function addBoutique(Boutique $boutique): static
    {
        if (!$this->boutiques->contains($boutique)) {
            $this->boutiques->add($boutique);
            $boutique->setVendeur($this);
        }
        return $this;
    }

    public function removeBoutique(Boutique $boutique): static
    {
        $this->boutiques->removeElement($boutique);
        return $this;
    }

    public function getBoutiqueActive(): ?Boutique
    {
        return $this->boutiques->first() ?: null;
    }

    /**
     * @return Collection<int, AbonnementSouscription>
     */
    public function getAbonnements(): Collection
    {
        return $this->abonnements;
    }

    public function abonnementActuel(): ?AbonnementSouscription
    {
        $now = new \DateTimeImmutable();
        foreach ($this->abonnements as $abonnement) {
            if ($abonnement->estActifAu($now)) {
                return $abonnement;
            }
        }
        return null;
    }
}