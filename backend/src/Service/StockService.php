<?php

namespace App\Service;

use App\Entity\LigneCommande;
use App\Entity\Variante;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Décrémentation automatique du stock à chaque vente, et lecture des
 * quantités vendues par couleur / taille (dashboard stock).
 */
class StockService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Décrémente le stock d'une variante pour la quantité vendue.
     */
    public function decrementer(Variante $variante, int $quantite): void
    {
        $variante->vendre($quantite);
    }

    /**
     * Applique la décrémentation pour toutes les lignes d'une commande.
     */
    public function appliquerCommande(iterable $lignes): void
    {
        foreach ($lignes as $ligne) {
            if ($ligne instanceof LigneCommande) {
                $this->decrementerPourLigne($ligne);
            }
        }
        $this->entityManager->flush();
    }

    private function decrementerPourLigne(LigneCommande $ligne): void
    {
        $produit = $ligne->getProduit();
        if ($produit === null) {
            return;
        }
        $match = $this->variante($produit, $ligne->getCouleur(), $ligne->getTaille());
        if ($match instanceof Variante) {
            $this->decrementer($match, $ligne->getQuantite());
        }
    }

    private function variante(\App\Entity\Produit $produit, ?string $couleur, ?string $taille): ?Variante
    {
        foreach ($produit->getVariantes() as $variante) {
            if ($variante->getCouleur() === $couleur && $variante->getTaille() === $taille) {
                return $variante;
            }
        }
        return null;
    }
}