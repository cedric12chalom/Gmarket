<?php

namespace App\Service;

use App\Entity\Categorie;
use App\Entity\Commande;
use App\Entity\DemandeProduit;
use App\Entity\LigneCommande;
use App\Entity\Litige;
use App\Entity\Livraison;
use App\Entity\Lot;
use App\Entity\Message;
use App\Entity\Notification;
use App\Entity\Paiement;
use App\Entity\Produit;
use App\Entity\User;
use App\Entity\Variete;
use Doctrine\ORM\EntityManagerInterface;

class EntitySerializer
{
    public static function user(User $user): array
    {
        $data = [
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            'telephone' => $user->getTelephone(),
            'role' => $user->getRole(),
            'statut' => $user->getStatut(),
            'latitude' => $user->getLatitude() !== null ? (float) $user->getLatitude() : null,
            'longitude' => $user->getLongitude() !== null ? (float) $user->getLongitude() : null,
            'solde' => (float) ($user->getSolde() ?? '0.00'),
            'date_inscription' => $user->getDateInscription()?->format('c'),
            'cgu_accepte_le' => $user->getCguAccepteLe()?->format('c'),
            'ville' => $user->getVille(),
            'quartier' => $user->getQuartier(),
            'localisation' => $user->getLocalisation(),
            'email_verifie' => $user->isEmailVerifie(),
        ];
        if ($user instanceof \App\Entity\Producteur) {
            $data['description_exploitation'] = $user->getDescriptionExploitation();
        }
        if ($user instanceof \App\Entity\Acheteur) {
            $data['adresse_livraison'] = $user->getAdresseLivraison();
        }
        if ($user instanceof \App\Entity\Livreur) {
            $data['type_transport'] = $user->getTypeTransport();
            $data['disponible'] = $user->isDisponible();
            $data['telephone_service'] = $user->getTelephoneService() ?? $user->getTelephone();
            $data['chat_actif'] = $user->isChatActif();
            $data['cni_numero'] = $user->getCniNumero();
            $data['cni_photo'] = $user->getCniPhoto();
            $data['photo_profil'] = $user->getPhotoProfil();
            $data['moyen_deplacement'] = $user->getMoyenDeplacement();
            $data['vehicule_plaque'] = $user->getVehiculePlaque();
            $data['vehicule_marque_modele'] = $user->getVehiculeMarqueModele();
            $data['mobile_money_numero'] = $user->getMobileMoneyNumero();
            $data['contact_urgence_nom'] = $user->getContactUrgenceNom();
            $data['contact_urgence_telephone'] = $user->getContactUrgenceTelephone();
            $data['conditions_livraison_accepte_le'] = $user->getConditionsLivraisonAccepteLe()?->format('c');
            $data['statut_verification'] = $user->getStatutVerification();
            $data['motif_refus'] = $user->getMotifRefus();
            $data['verifie_le'] = $user->getVerifieLe()?->format('c');
            $data['valide_le'] = $user->getValideLe()?->format('c');
        }
        return $data;
    }

    public static function categorie(Categorie $categorie): array
    {
        return [
            'id' => $categorie->getId(),
            'nom' => $categorie->getNom(),
            'type_transport' => $categorie->getTypeTransport(),
            'commission_fixe' => (float) $categorie->getCommissionFixe(),
            'commission_pourcent' => (float) $categorie->getCommissionPourcent(),
            'approuve' => $categorie->isApprouve(),
        ];
    }

    public static function produit(Produit $produit): array
    {
        return [
            'id' => $produit->getId(),
            'nom' => $produit->getNom(),
            'description' => $produit->getDescription(),
            'unite' => $produit->getUnite(),
            'prix_min' => (float) $produit->getPrixMin(),
            'prix_max' => (float) $produit->getPrixMax(),
            'photo' => $produit->getPhoto(),
            'categorie' => $produit->getCategorie() ? self::categorie($produit->getCategorie()) : null,
            'varietes' => array_map(fn(Variete $variete) => self::variete($variete), $produit->getVarietes()->toArray()),
        ];
    }

    public static function variete(Variete $variete): array
    {
        return [
            'id' => $variete->getId(),
            'nom' => $variete->getNom(),
            'produit_id' => $variete->getProduit()?->getId(),
        ];
    }

    public static function lot(Lot $lot, bool $compactProduit = false): array
    {
        $produit = $lot->getProduit();
        $producteur = $lot->getProducteur();
        $commission = $produit?->getCategorie() ? $produit->getCategorie()->calculerCommission((float) $lot->getPrixProducteur()) : 0.0;
        return [
            'id' => $lot->getId(),
            'produit' => $compactProduit && $produit ? [
                'id' => $produit->getId(),
                'nom' => $produit->getNom(),
                'unite' => $produit->getUnite(),
            ] : ($produit ? self::produit($produit) : null),
            'producteur' => $producteur ? self::user($producteur) : null,
            'quantite_disponible' => (float) $lot->getQuantiteDisponible(),
            'quantite_reservee' => (float) $lot->getQuantiteReservee(),
            'prix_producteur' => (float) $lot->getPrixProducteur(),
            'prix_reduit' => $lot->calculerPrixReduit(),
            'prix_acheteur' => (float) ($lot->calculerPrixReduit() ?? $lot->getPrixProducteur()) + $commission,
            'date_recolte' => $lot->getDateRecolte()?->format('Y-m-d'),
            'date_expiration' => $lot->getDateExpiration()?->format('Y-m-d'),
            'duree_conservation' => $lot->getDureeConservation(),
            'jours_avant_retrait' => $lot->getJoursAvantRetrait(),
            'variete' => $lot->getVariete() ? self::variete($lot->getVariete()) : null,
            'qr_code' => $lot->getQrCode(),
            'statut' => $lot->getStatut(),
            'indice_fraicheur' => $lot->calculerIndiceFraicheur(),
            'latitude' => $lot->getLatitude() ?? $producteur?->getLatitude(),
            'longitude' => $lot->getLongitude() ?? $producteur?->getLongitude(),
            'distance_km' => $lot->distanceKm,
        ];
    }

    public static function commande(Commande $commande): array
    {
        return [
            'id' => $commande->getId(),
            'acheteur' => $commande->getAcheteur() ? self::user($commande->getAcheteur()) : null,
            'statut' => $commande->getStatut(),
            'mode_recuperation' => $commande->getModeRecuperation(),
            'date_commande' => $commande->getDateCommande()?->format('c'),
            'montant_produits' => (float) $commande->getMontantProduits(),
            'commission' => (float) $commande->getCommission(),
            'frais_livraison' => (float) $commande->getFraisLivraison(),
            'montant_total' => (float) $commande->getMontantTotal(),
            'lignes' => array_map(fn(LigneCommande $ligne) => self::ligneCommande($ligne), $commande->getLignes()->toArray()),
        ];
    }

    public static function ligneCommande(LigneCommande $ligne): array
    {
        return [
            'id' => $ligne->getId(),
            'lot' => $ligne->getLot() ? self::lot($ligne->getLot(), true) : null,
            'quantite' => $ligne->getQuantite(),
            'prix_unitaire' => $ligne->getPrixUnitaire(),
            'sous_total' => $ligne->getSousTotal(),
        ];
    }

    public static function paiement(Paiement $paiement): array
    {
        return [
            'id' => $paiement->getId(),
            'commande_id' => $paiement->getCommande()?->getId(),
            'montant' => (float) $paiement->getMontant(),
            'methode' => $paiement->getMethode(),
            'numero' => $paiement->getNumero(),
            'statut' => $paiement->getStatut(),
            'reference' => $paiement->getReference(),
            'date_paiement' => $paiement->getDatePaiement()?->format('c'),
        ];
    }

    public static function livraison(Livraison $livraison): array
    {
        return [
            'id' => $livraison->getId(),
            'commande' => $livraison->getCommande() ? self::commande($livraison->getCommande()) : null,
            'livreur' => $livraison->getLivreur() ? self::user($livraison->getLivreur()) : null,
            'statut' => $livraison->getStatut(),
            'adresse_livraison' => $livraison->getAdresseLivraison(),
            'date_retrait' => $livraison->getDateRetrait()?->format('c'),
            'date_livraison' => $livraison->getDateLivraison()?->format('c'),
            'date_reception_confirmee' => $livraison->getDateReceptionConfirmee()?->format('c'),
            'photo_retrait' => $livraison->getPhotoRetrait(),
            'photo_livraison' => $livraison->getPhotoLivraison(),
            'frais' => (float) $livraison->getFrais(),
            'position_acheteur_figee' => true,
        ];
    }

    public static function litige(Litige $litige, ?EntityManagerInterface $em = null): array
    {
        $data = [
            'id' => $litige->getId(),
            'commande' => $litige->getCommande() ? self::commande($litige->getCommande()) : null,
            'motif' => $litige->getMotif(),
            'description' => $litige->getDescription(),
            'statut' => $litige->getStatut(),
            'date_creation' => $litige->getDateCreation()?->format('c'),
            'resolution' => $litige->getResolution(),
            'admin_traiteur' => $litige->getAdminTraiteur() ? self::user($litige->getAdminTraiteur()) : null,
        ];

        if ($em !== null && $litige->getCommande()) {
            $livraison = $em->getRepository(Livraison::class)->findOneBy(['commande' => $litige->getCommande()]);
            if ($livraison) {
                $data['livraison'] = [
                    'id' => $livraison->getId(),
                    'livreur' => $livraison->getLivreur() ? self::user($livraison->getLivreur()) : null,
                    'statut' => $livraison->getStatut(),
                    'adresse_livraison' => $livraison->getAdresseLivraison(),
                    'date_retrait' => $livraison->getDateRetrait()?->format('c'),
                    'date_livraison' => $livraison->getDateLivraison()?->format('c'),
                    'frais' => (float) $livraison->getFrais(),
                ];
            }
        }

        return $data;
    }

    public static function message(Message $message): array
    {
        return [
            'id' => $message->getId(),
            'livraison_id' => $message->getLivraison()?->getId(),
            'expediteur' => $message->getExpediteur() ? self::user($message->getExpediteur()) : null,
            'contenu' => $message->getContenu(),
            'date_envoi' => $message->getDateEnvoi()?->format('c'),
            'lu' => $message->isLu(),
        ];
    }

    public static function notification(Notification $notification): array
    {
        return [
            'id' => $notification->getId(),
            'titre' => $notification->getTitre(),
            'message' => $notification->getMessage(),
            'lu' => $notification->isLu(),
            'date_envoi' => $notification->getDateEnvoi()?->format('c'),
        ];
    }

    public static function demandeProduit(DemandeProduit $demande): array
    {
        return [
            'id' => $demande->getId(),
            'producteur' => self::user($demande->getProducteur()),
            'nom_produit' => $demande->getNomProduit(),
            'categorie_suggeree' => $demande->getCategorieSuggeree(),
            'description' => $demande->getDescription(),
            'unite' => $demande->getUnite(),
            'statut' => $demande->getStatut(),
            'motif_refus' => $demande->getMotifRefus(),
            'date_creation' => $demande->getDateCreation()?->format('c'),
            'date_traitement' => $demande->getDateTraitement()?->format('c'),
        ];
    }
}
