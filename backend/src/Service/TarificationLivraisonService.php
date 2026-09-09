<?php

namespace App\Service;

use App\Entity\LigneCommande;
use App\Entity\Livreur;
use App\Entity\User;

/**
 * Tarification dynamique des frais de livraison, calculée à la création de la
 * commande (le livreur pouvant être assigné seulement après paiement, la
 * référence du trajet de ramassage est le livreur choisi au checkout, sinon le
 * meilleur livreur disponible le plus proche, sinon 0 km).
 *
 * Formule : base + (50 FCFA × km de ramassage) + (100 FCFA × km de livraison),
 * plancher et plafond configurables. Au-delà du plafond, la livraison est
 * explicitement refusée (plafond_depasse).
 */
class TarificationLivraisonService
{
    public function __construct(
        private LivraisonGeoService $geoService,
        private float $base = 200.0,
        private float $tarifRamassageKm = 50.0,
        private float $tarifLivraisonKm = 100.0,
        private float $fraisMin = 300.0,
        private float $fraisMax = 20000.0,
    ) {
    }

    public function getPlafond(): float
    {
        return $this->fraisMax;
    }

    /**
     * Calcule les frais de livraison d'une commande (ou d'une estimation).
     *
     * @param iterable<LigneCommande> $lignes
     *
     * @return array{
     *     frais: int|null,
     *     distance_ramassage_km: float,
     *     distance_livraison_km: float,
     *     plafond_depasse: bool
     * }
     */
    public function calculerFrais(iterable $lignes, ?User $acheteur, ?Livreur $livreurReference): array
    {
        $origine = $this->geoService->pointOrigine($lignes);
        $destination = $this->geoService->pointDestination($acheteur);
        if ($origine === null || $destination === null) {
            return ['frais' => null, 'distance_ramassage_km' => 0.0, 'distance_livraison_km' => 0.0, 'plafond_depasse' => false];
        }

        $distanceLivraison = $this->geoService->distance($origine, $destination);
        $distanceRamassage = 0.0;
        if ($livreurReference && $livreurReference->getLatitude() && $livreurReference->getLongitude()) {
            $distanceRamassage = $this->geoService->distance($origine, [
                'lat' => (float) $livreurReference->getLatitude(),
                'lng' => (float) $livreurReference->getLongitude(),
            ]);
        }

        $kmRamassage = $distanceRamassage / 1000.0;
        $kmLivraison = $distanceLivraison / 1000.0;
        $frais = $this->base + ($kmRamassage * $this->tarifRamassageKm) + ($kmLivraison * $this->tarifLivraisonKm);
        if ($frais < $this->fraisMin) {
            $frais = $this->fraisMin;
        }
        $plafondDepasse = $frais > $this->fraisMax;
        $frais = min($frais, $this->fraisMax);

        return [
            'frais' => (int) round($frais),
            'distance_ramassage_km' => round($kmRamassage, 2),
            'distance_livraison_km' => round($kmLivraison, 2),
            'plafond_depasse' => $plafondDepasse,
        ];
    }
}
