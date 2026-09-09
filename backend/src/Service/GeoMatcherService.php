<?php

namespace App\Service;

use App\Entity\Livraison;
use App\Entity\Livreur;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class GeoMatcherService
{
    private RoutingService $routingService;
    private LivraisonGeoService $geoService;
    private EntityManagerInterface $em;

    public function __construct(RoutingService $routingService, LivraisonGeoService $geoService, EntityManagerInterface $em)
    {
        $this->routingService = $routingService;
        $this->geoService = $geoService;
        $this->em = $em;
    }

    /**
     * Meilleur livreur disponible (actif) pour un point de ramassage donné,
     * basé sur le score distance + durée du trajet. Retourne null si aucun
     * livreur n'est utilisable.
     *
     * @return array{livreur: Livreur, distance: float, duration: float, geometry: array, score: float}|null
     */
    public function trouverMeilleurLivreur(float $origLat, float $origLng): ?array
    {
        $livreurs = $this->em->getRepository(User::class)->findByRoleWithCoordinates('livreur');
        $available = array_values(array_filter(
            $livreurs,
            fn(Livreur $l) => $l->isDisponible() && $l->isActif()
        ));

        if (empty($available)) {
            return null;
        }

        $candidates = [];
        foreach ($available as $livreur) {
            $lat = (float) $livreur->getLatitude();
            $lng = (float) $livreur->getLongitude();
            if ($lat === 0.0 && $lng === 0.0) {
                continue;
            }

            $route = $this->routingService->getRoute($lat, $lng, $origLat, $origLng);
            $distance = $route['distance'];
            $duration = $route['duration'];
            $score = $distance + ($duration * 5);

            $candidates[] = [
                'livreur' => $livreur,
                'distance' => $distance,
                'duration' => $duration,
                'geometry' => $route['geometry'],
                'score' => $score,
            ];
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, fn(array $a, array $b) => $a['score'] <=> $b['score']);
        return $candidates[0];
    }

    public function findBestMatchForDelivery(Livraison $livraison): ?array
    {
        $commande = $livraison->getCommande();
        if (!$commande instanceof \App\Entity\Commande) {
            return null;
        }

        $origine = $this->geoService->pointOrigine($commande->getLignes());
        if ($origine === null) {
            return null;
        }

        return $this->trouverMeilleurLivreur($origine['lat'], $origine['lng']);
    }
}
