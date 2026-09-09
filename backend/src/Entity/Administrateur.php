<?php

namespace App\Entity;

use App\Repository\AdministrateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdministrateurRepository::class)]
class Administrateur extends User
{
    #[ORM\OneToMany(targetEntity: Litige::class, mappedBy: 'adminTraiteur')]
    private Collection $litiges;

    public function __construct()
    {
        parent::__construct();
        $this->litiges = new ArrayCollection();
    }

    public function getLitiges(): Collection
    {
        return $this->litiges;
    }

    public function addLitige(Litige $litige): self
    {
        if (!$this->litiges->contains($litige)) {
            $this->litiges->add($litige);
            $litige->setAdminTraiteur($this);
        }
        return $this;
    }

    public function removeLitige(Litige $litige): self
    {
        if ($this->litiges->removeElement($litige)) {
            if ($litige->getAdminTraiteur() === $this) {
                $litige->setAdminTraiteur(null);
            }
        }
        return $this;
    }

    // Use-case: gererLitiges() — exposes assigned litiges.
    public function gererLitiges(): Collection
    {
        return $this->litiges;
    }

    // Use-case: gererUtilisateurs() — realized via UtilisateurController; helper for role access.
    public function gererUtilisateurs(): bool
    {
        return true;
    }

    // Use-case: definirPrix() — validated via Produit::validerPrix().
    public function definirPrix(Produit $produit, float $prix): bool
    {
        return $produit->validerPrix($prix);
    }

    // Use-case: consulterStatistiques() — realized via AdminController::stats().
    public function consulterStatistiques(): bool
    {
        return true;
    }
}
