<?php

namespace App\Service;

use App\Entity\LigneCommande;
use App\Entity\User;

/**
 * Source unique et faisant autorité pour les positions et distances d'une
 * livraison : point de retrait (chez le producteur) et point de dépôt (chez
 * l'acheteur). Priorité de position du producteur : position du lot d'abord,
 * puis position du producteur en repli. Réutilisé par la tarification
 * (TarificationLivraisonService) et par l'affichage de la route (GeoController),
 * pour qu'un seul et même calcul serve partout.
 */
class LivraisonGeoService
{
    private RoutingService $routingService;

    public function __construct(RoutingService $routingService)
    {
        $this->routingService = $routingService;
    }

    /**
     * Point de retrait d'une commande : position du lot, puis position du
     * producteur en repli (premier lot positionné).
     *
     * @param iterable<LigneCommande> $lignes
     *
     * @return array{lat: float, lng: float}|null
     */
    public function pointOrigine(iterable $lignes): ?array
    {
        foreach ($lignes as $ligne) {
            if (!$ligne instanceof LigneCommande) {
                continue;
            }
            $lot = $ligne->getLot();
            if ($lot === null) {
                continue;
            }
            $lat = $lot->getLatitude() ?? $lot->getProducteur()?->getLatitude();
            $lng = $lot->getLongitude() ?? $lot->getProducteur()?->getLongitude();
            if ($lat !== null && $lng !== null && $lat !== '' && $lng !== '') {
                return ['lat' => (float) $lat, 'lng' => (float) $lng];
            }
        }

        return null;
    }

    /**
     * Point de livraison : position de l'acheteur.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function pointDestination(?User $acheteur): ?array
    {
        if ($acheteur && $acheteur->getLatitude() && $acheteur->getLongitude()) {
            return ['lat' => (float) $acheteur->getLatitude(), 'lng' => (float) $acheteur->getLongitude()];
        }

        return null;
    }

    /**
     * Distance entre deux points en mètres, via le moteur de routage existant
     * (OSRM en distance réelle, repli Haversine en vol d'oiseau si OSRM est
     * indisponible). Ne lève jamais d'exception.
     *
     * @param array{lat: float, lng: float} $a
     * @param array{lat: float, lng: float} $b
     */
    public function distance(array $a, array $b): float
    {
        return (float) $this->routingService->getRouteMulti([$a, $b])['distance'];
    }
}
