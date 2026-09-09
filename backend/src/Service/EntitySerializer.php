<?php

namespace App\Service;

use App\Entity\Acheteur;
use App\Entity\Administrateur;
use App\Entity\Avis;
use App\Entity\Boutique;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\Produit;
use App\Entity\User;
use App\Entity\Variante;

/**
 * Sérialisation manuelle des entités vers des tableaux pour l'API JSON.
 * Aucune dépendance à un bundle serializer lourd.
 */
class EntitySerializer
{
    public function user(User $user): array
    {
        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'photo' => $user->getPhoto(),
            'telephone' => $user->getTelephone(),
            'tiktok' => $user->getTiktok(),
            'instagram' => $user->getInstagram(),
            'snapchat' => $user->getSnapchat(),
            'facebook' => $user->getFacebook(),
            'createdAt' => $user->getCreatedAt()->format('c'),
            'roles' => $user->getRoles(),
        ];

        if ($user instanceof Acheteur) {
            $data['prenom'] = $user->getPrenom();
            $data['nom'] = $user->getNom();
            $data['prenomNom'] = $user->getPrenomNom();
        }
        if ($user instanceof \App\Entity\Vendeur) {
            $data['prenom'] = $user->getPrenom();
            $data['nom'] = $user->getNom();
            $data['prenomNom'] = $user->getPrenomNom();
            $data['boutiques'] = array_map(fn (Boutique $b) => $this->boutiqueResume($b), $user->getBoutiques()->toArray());
            $data['abonnement'] = $user->abonnementActuel() ? [
                'nom' => $user->abonnementActuel()->getAbonnement()->getNom(),
                'statut' => $user->abonnementActuel()->getStatut()->value,
                'dateFin' => $user->abonnementActuel()->getDateFin()?->format('c'),
                'joursRestants' => $user->abonnementActuel()->joursRestants(new \DateTimeImmutable()),
            ] : null;
        }
        if ($user instanceof Administrateur) {
            $data['nom'] = $user->getNom();
        }

        return $data;
    }

    public function boutique(Boutique $boutique): array
    {
        $produits = $boutique->getProduits();
        $stockTotal = 0;
        foreach ($produits as $produit) {
            $stockTotal += $produit->getStockTotal();
        }

        return [
            'id' => $boutique->getId(),
            'nom' => $boutique->getNom(),
            'slug' => $boutique->getSlug(),
            'description' => $boutique->getDescription(),
            'banniere' => $boutique->getBanniere(),
            'logo' => $boutique->getLogo(),
            'theme' => $boutique->getTheme()->value,
            'themeRendu' => $boutique->getTheme()->rendu(),
            'themeAccent' => $boutique->getTheme()->accent(),
            'tiktokPseudo' => $boutique->getTiktokPseudo(),
            'aLivreur' => $boutique->getALivreur(),
            'livreurDetail' => $boutique->getLivreurDetail(),
            'createdAt' => $boutique->getCreatedAt()->format('c'),
            'lienPartageable' => $boutique->getLienPartageable(),
            'produitCount' => count($produits),
            'stockTotal' => $stockTotal,
            'vendeur' => $boutique->getVendeur() ? $this->vendeurResume($boutique->getVendeur()) : null,
        ];
    }

    public function boutiqueResume(Boutique $boutique): array
    {
        return [
            'id' => $boutique->getId(),
            'nom' => $boutique->getNom(),
            'slug' => $boutique->getSlug(),
            'banniere' => $boutique->getBanniere(),
            'logo' => $boutique->getLogo(),
            'theme' => $boutique->getTheme()->value,
            'themeAccent' => $boutique->getTheme()->accent(),
            'tiktokPseudo' => $boutique->getTiktokPseudo(),
            'lienPartageable' => $boutique->getLienPartageable(),
        ];
    }

    public function vendeurResume(\App\Entity\Vendeur $vendeur): array
    {
        return [
            'id' => $vendeur->getId(),
            'prenomNom' => $vendeur->getPrenomNom(),
            'photo' => $vendeur->getPhoto(),
            'tiktok' => $vendeur->getTiktok(),
            'instagram' => $vendeur->getInstagram(),
            'snapchat' => $vendeur->getSnapchat(),
            'facebook' => $vendeur->getFacebook(),
        ];
    }

    public function produit(Produit $produit): array
    {
        $offre = $produit->getOffre();
        return [
            'id' => $produit->getId(),
            'nom' => $produit->getNom(),
            'description' => $produit->getDescription(),
            'images' => $produit->getImages(),
            'imagePrincipale' => $produit->getImagePrincipale(),
            'prix' => $produit->getPrix(),
            'prixActuel' => $produit->getPrixActuel(),
            'actif' => $produit->isActif(),
            'createdAt' => $produit->getCreatedAt()->format('c'),
            'stockTotal' => $produit->getStockTotal(),
            'totalVendu' => $produit->getTotalVendu(),
            'couleurs' => $produit->getCouleurs(),
            'tailles' => $produit->getTailles(),
            'categorie' => $produit->getCategorie() ? [
                'id' => $produit->getCategorie()->getId(),
                'nom' => $produit->getCategorie()->getNom(),
                'slug' => $produit->getCategorie()->getSlug(),
            ] : null,
            'boutique' => $produit->getBoutique() ? $this->boutiqueResume($produit->getBoutique()) : null,
            'variantes' => array_map(fn (Variante $v) => $this->variante($v), $produit->getVariantes()->toArray()),
            'offre' => $offre ? [
                'id' => $offre->getId(),
                'prixPromo' => $offre->getPrixPromo(),
                'dateDebut' => $offre->getDateDebut()?->format('c'),
                'dateFin' => $offre->getDateFin()?->format('c'),
                'enCours' => $offre->estEnCours(),
                'secondesRestantes' => $offre->secondesRestantes(),
                'pourcentageReduction' => $offre->pourcentageReduction(),
            ] : null,
        ];
    }

    public function variante(Variante $variante): array
    {
        return [
            'id' => $variante->getId(),
            'couleur' => $variante->getCouleur(),
            'taille' => $variante->getTaille(),
            'stock' => $variante->getStock(),
            'stockVendu' => $variante->getStockVendu(),
        ];
    }

    public function commande(Commande $commande): array
    {
        return [
            'id' => $commande->getId(),
            'statut' => $commande->getStatut()->value,
            'statutLibelle' => $this->libelleStatut($commande->getStatut()->value),
            'montantTotal' => $commande->getMontantTotal(),
            'montantProduits' => $commande->getMontantProduits(),
            'commission' => $commande->getCommission(),
            'createdAt' => $commande->getCreatedAt()->format('c'),
            'boutique' => $commande->getBoutique() ? $this->boutiqueResume($commande->getBoutique()) : null,
            'acheteur' => $commande->getAcheteur() ? $this->acheteurResume($commande->getAcheteur()) : null,
            'lignes' => array_map(fn (LigneCommande $l) => $this->ligneCommande($l), $commande->getLignes()->toArray()),
            'paiement' => $commande->getPaiement() ? [
                'id' => $commande->getPaiement()->getId(),
                'montant' => $commande->getPaiement()->getMontant(),
                'statut' => $commande->getPaiement()->getStatut()->value,
            ] : null,
            'peutLaisserAvis' => $commande->getStatut()->ouvreAvis() && $commande->getAvis() === null,
        ];
    }

    public function ligneCommande(LigneCommande $ligne): array
    {
        return [
            'id' => $ligne->getId(),
            'produit' => $ligne->getProduit() ? [
                'id' => $ligne->getProduit()->getId(),
                'nom' => $ligne->getProduit()->getNom(),
                'imagePrincipale' => $ligne->getProduit()->getImagePrincipale(),
            ] : null,
            'couleur' => $ligne->getCouleur(),
            'taille' => $ligne->getTaille(),
            'quantite' => $ligne->getQuantite(),
            'prixUnitaire' => $ligne->getPrixUnitaire(),
            'sousTotal' => $ligne->getSousTotal(),
        ];
    }

    public function avis(Avis $avis): array
    {
        return [
            'id' => $avis->getId(),
            'note' => $avis->getNote(),
            'commentaire' => $avis->getCommentaire(),
            'createdAt' => $avis->getCreatedAt()->format('c'),
            'acheteur' => $avis->getAcheteur() ? $this->acheteurResume($avis->getAcheteur()) : null,
        ];
    }

    public function acheteurResume(Acheteur $acheteur): array
    {
        return [
            'id' => $acheteur->getId(),
            'prenomNom' => $acheteur->getPrenomNom(),
            'photo' => $acheteur->getPhoto(),
        ];
    }

    private function libelleStatut(string $statut): string
    {
        return match ($statut) {
            'en_attente' => 'En attente',
            'expediee' => 'Expédiée',
            'livree' => 'Livrée',
            'annulee' => 'Annulée',
            default => $statut,
        };
    }
}