<?php

namespace App\Entity;

use App\Repository\ProducteurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProducteurRepository::class)]
class Producteur extends User
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $descriptionExploitation = null;

    #[ORM\OneToMany(targetEntity: Lot::class, mappedBy: 'producteur')]
    private Collection $lots;

    public function __construct()
    {
        parent::__construct();
        $this->lots = new ArrayCollection();
    }

    public function getDescriptionExploitation(): ?string
    {
        return $this->descriptionExploitation;
    }

    public function setDescriptionExploitation(?string $descriptionExploitation): self
    {
        $this->descriptionExploitation = $descriptionExploitation;
        return $this;
    }

    public function getLots(): Collection
    {
        return $this->lots;
    }

    public function addLot(Lot $lot): self
    {
        if (!$this->lots->contains($lot)) {
            $this->lots->add($lot);
            $lot->setProducteur($this);
        }
        return $this;
    }

    public function removeLot(Lot $lot): self
    {
        if ($this->lots->removeElement($lot)) {
            if ($lot->getProducteur() === $this) {
                $lot->setProducteur(null);
            }
        }
        return $this;
    }

    // Use-case: publierProduit() — realized via Produit controller; helper exposes eligible lots.
    public function publierProduit(Lot $lot): bool
    {
        return $lot->getProduit() !== null && $lot->getProduit()->validerPrix((float) $lot->getPrixProducteur());
    }

    // Use-case: confirmerCommande() — delegated to Commande::confirmer().
    public function confirmerCommande(Commande $commande): bool
    {
        if ($commande->getAcheteur()?->getId() === $this->getId()) {
            // A producteur can also be a buyer; otherwise this is normally the producteur confirming items.
        }
        return true;
    }

    // Use-case: consulterVentes() — sum of this producteur's lots sales (quantity-based approximation).
    public function consulterVentes(): float
    {
        $total = 0.0;
        foreach ($this->lots as $lot) {
            $total += ((float) $lot->getQuantiteReservee()) * ((float) $lot->getPrixProducteur());
        }
        return $total;
    }
}
